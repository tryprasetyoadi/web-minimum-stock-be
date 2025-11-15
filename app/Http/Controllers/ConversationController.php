<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $all = filter_var($request->query('all', 'false'), FILTER_VALIDATE_BOOLEAN);
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        $user = $request->user();

        $query = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->whereKey($user->id))
            ->withCount('participants')
            ->with(['participants:id,first_name,last_name,profile_picture', 'lastMessage'])
            ->orderBy('id', 'desc')
            ->select(['id', 'name', 'is_group', 'created_by']);

        if ($all) {
            $list = $query->get();
        } else {
            $paginator = $query->paginate($perPage);
            $list = $paginator->items();
        }

        $data = array_map(function ($conv) {
            $participants = $conv->participants->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => trim(($u->first_name ?? '').' '.($u->last_name ?? '')),
                    'profile_picture_url' => $u->profile_picture ? Storage::url($u->profile_picture) : null,
                ];
            })->all();

            $last = $conv->lastMessage;
            $lastMessage = $last ? [
                'id' => $last->id,
                'user_id' => $last->user_id,
                'body' => $last->body,
                'created_at' => $last->created_at,
            ] : null;

            return [
                'id' => $conv->id,
                'name' => $conv->name,
                'is_group' => (bool) $conv->is_group,
                'created_by' => $conv->created_by,
                'participants_count' => $conv->participants_count,
                'participants' => $participants,
                'last_message' => $lastMessage,
            ];
        }, $list);

        if ($all) {
            return response()->json([
                'message' => 'Conversations fetched successfully',
                'data' => $data,
            ]);
        }

        return response()->json([
            'message' => 'Conversations fetched successfully',
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'is_group' => ['boolean'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $participantIds = array_values(array_unique(array_merge($validated['participant_ids'], [$user->id])));

        $conversation = DB::transaction(function () use ($validated, $user, $participantIds) {
            $conv = Conversation::create([
                'name' => $validated['name'] ?? null,
                'is_group' => (bool) ($validated['is_group'] ?? (count($participantIds) > 2)),
                'created_by' => $user->id,
            ]);

            $conv->participants()->sync($participantIds);

            return $conv;
        });

        return response()->json([
            'message' => 'Conversation created',
            'id' => $conversation->id,
        ], 201);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        if (! $conversation->participants()->whereKey($user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $all = filter_var($request->query('all', 'false'), FILTER_VALIDATE_BOOLEAN);
        $perPage = max(1, min((int) $request->query('per_page', 20), 100));
        $order = strtolower($request->query('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with(['user:id,first_name,last_name,profile_picture'])
            ->orderBy('created_at', $order)
            ->select(['id','conversation_id','user_id','body','created_at','read_at']);

        if ($all) {
            $list = $query->get();
        } else {
            $paginator = $query->paginate($perPage);
            $list = $paginator->items();
        }

        $data = array_map(function ($msg) {
            return [
                'id' => $msg->id,
                'conversation_id' => $msg->conversation_id,
                'user' => [
                    'id' => optional($msg->user)->id,
                    'name' => trim((optional($msg->user)->first_name ?? '').' '.(optional($msg->user)->last_name ?? '')),
                    'profile_picture_url' => optional($msg->user)->profile_picture ? Storage::url(optional($msg->user)->profile_picture) : null,
                ],
                'body' => $msg->body,
                'created_at' => $msg->created_at,
                'read_at' => $msg->read_at,
            ];
        }, $list);

        if ($all) {
            return response()->json([
                'message' => 'Messages fetched successfully',
                'data' => $data,
            ]);
        }

        return response()->json([
            'message' => 'Messages fetched successfully',
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (! $conversation->participants()->whereKey($user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        $message->load('user');
        event(new MessageSent($message));

        return response()->json([
            'message' => 'Message sent',
            'id' => $message->id,
        ], 201);
    }
}