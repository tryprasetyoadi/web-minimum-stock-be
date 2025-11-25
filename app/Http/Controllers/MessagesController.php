<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessagesController extends Controller
{
    /**
     * List messages by folder: inbox|sent|history
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $folder = $request->query('folder', 'inbox');
        $perPage = max(1, min((int) $request->query('per_page', 10), 100));
        $q = trim((string) $request->query('q', ''));

        // Conversations where current user participates (direct one-to-one assumed)
        $convIds = Conversation::whereHas('participants', fn($q2) => $q2->whereKey($user->id))
            ->pluck('id');

        $query = Message::query()
            ->whereIn('conversation_id', $convIds)
            ->with(['user:id,name,first_name,last_name'])
            ->orderByDesc('created_at');

        if ($folder === 'inbox') {
            $query->where('user_id', '<>', $user->id);
        } elseif ($folder === 'sent') {
            $query->where('user_id', $user->id);
        } // history = all

        if ($q !== '') {
            $query->where('body', 'like', "%$q%");
        }

        $paginator = $query->paginate($perPage);
        $items = $paginator->items();

        // mark delivered for messages to the current user
        foreach ($items as $msg) {
            if ($msg->user_id !== $user->id && $msg->delivered_at === null) {
                $msg->delivered_at = now();
                $msg->save();
            }
        }

        $data = array_map(function ($msg) use ($user) {
            // partner = other participant in conversation
            $partnerId = DB::table('conversation_user')
                ->where('conversation_id', $msg->conversation_id)
                ->where('user_id', '<>', $user->id)
                ->value('user_id');
            $partner = User::find($partnerId);

            $status = $msg->read_at ? 'read' : ($msg->delivered_at ? 'delivered' : 'sent');

            return [
                'id' => $msg->id,
                'sender_id' => $msg->user_id,
                'receiver_id' => $partnerId,
                'text' => $msg->body,
                'status' => $status,
                'created_at' => $msg->created_at?->toIso8601String(),
                'updated_at' => $msg->updated_at?->toIso8601String(),
                'read_at' => $msg->read_at?->toIso8601String(),
                'toId' => $partnerId,
                'toName' => $partner?->name,
            ];
        }, $items);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Send message: ensure direct conversation exists then create message
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'to_id' => ['required', 'integer', 'exists:users,id'],
            'text' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $user = $request->user();
        if ($validated['to_id'] == $user->id) {
            return response()->json(['message' => 'Cannot send to self'], 422);
        }

        // Find or create direct conversation (non-group) with two participants
        $conv = Conversation::query()
            ->where('is_group', false)
            ->whereHas('participants', fn($q) => $q->whereKey($user->id))
            ->whereHas('participants', fn($q) => $q->whereKey($validated['to_id']))
            ->first();

        if (! $conv) {
            $conv = Conversation::create([
                'name' => null,
                'is_group' => false,
                'created_by' => $user->id,
            ]);
            $conv->participants()->sync([$user->id, $validated['to_id']]);
        }

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'user_id' => $user->id,
            'body' => $validated['text'],
        ]);

        return response()->json([
            'id' => $msg->id,
            'sender_id' => $msg->user_id,
            'receiver_id' => $validated['to_id'],
            'text' => $msg->body,
            'status' => 'sent',
            'created_at' => $msg->created_at?->toIso8601String(),
            'updated_at' => $msg->updated_at?->toIso8601String(),
            'read_at' => null,
        ], 201);
    }

    /**
     * List conversation items for current user
     */
    public function conversations(Request $request)
    {
        $user = $request->user();
        $perPage = max(1, min((int) $request->query('per_page', 10), 100));

        $query = Conversation::query()
            ->where('is_group', false)
            ->whereHas('participants', fn($q) => $q->whereKey($user->id))
            ->with(['lastMessage', 'participants' => fn($q) => $q->select('users.id','users.name')])
            ->orderByDesc('updated_at');

        $paginator = $query->paginate($perPage);
        $items = $paginator->items();

        $data = array_map(function ($conv) use ($user) {
            $partner = $conv->participants->firstWhere('id', '!=', $user->id);
            $unread = Message::where('conversation_id', $conv->id)
                ->where('user_id', '<>', $user->id)
                ->whereNull('read_at')
                ->count();

            return [
                'id' => $conv->id,
                'partner_id' => optional($partner)->id,
                'partner_name' => optional($partner)->name,
                'last_message_preview' => optional($conv->lastMessage)->body ?? '',
                'last_message_at' => optional($conv->lastMessage?->created_at)->toIso8601String(),
                'unread_count' => $unread,
            ];
        }, $items);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Conversation messages for partner_id with optional since
     */
    public function conversationMessages(Request $request, int $partnerId)
    {
        $user = $request->user();
        $perPage = max(1, min((int) $request->query('per_page', 50), 100));
        $since = $request->query('since');

        $conv = Conversation::query()
            ->where('is_group', false)
            ->whereHas('participants', fn($q) => $q->whereKey($user->id))
            ->whereHas('participants', fn($q) => $q->whereKey($partnerId))
            ->first();

        if (! $conv) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $query = Message::query()
            ->where('conversation_id', $conv->id)
            ->orderBy('created_at', 'asc');

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        $paginator = $query->paginate($perPage);
        $items = $paginator->items();

        // mark delivered for messages to the current user
        foreach ($items as $msg) {
            if ($msg->user_id !== $user->id && $msg->delivered_at === null) {
                $msg->delivered_at = now();
                $msg->save();
            }
        }

        $data = array_map(function ($msg) {
            $status = $msg->read_at ? 'read' : ($msg->delivered_at ? 'delivered' : 'sent');
            return [
                'id' => $msg->id,
                'sender_id' => $msg->user_id,
                'text' => $msg->body,
                'status' => $status,
                'created_at' => $msg->created_at?->toIso8601String(),
                'updated_at' => $msg->updated_at?->toIso8601String(),
                'read_at' => $msg->read_at?->toIso8601String(),
            ];
        }, $items);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Mark message read
     */
    public function markRead(Request $request, int $id)
    {
        $user = $request->user();
        $msg = Message::find($id);
        if (! $msg) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $conv = Conversation::find($msg->conversation_id);
        dd($conv);
        $isParticipant = $conv && $conv->participants()->whereKey($user->id)->exists();
        if (! $isParticipant || $msg->user_id === $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $msg->read_at = now();
        $msg->save();

        return response()->json([
            'id' => $msg->id,
            'status' => 'read',
            'read_at' => $msg->read_at?->toIso8601String(),
            'updated_at' => $msg->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Unread count across conversations
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $convIds = Conversation::whereHas('participants', fn($q) => $q->whereKey($user->id))
            ->pluck('id');

        $count = Message::whereIn('conversation_id', $convIds)
            ->where('user_id', '<>', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'inbox_unread' => $count,
            'total_unread' => $count,
        ]);
    }

    /**
     * Incremental updates by updated_after
     */
    public function updates(Request $request)
    {
        $user = $request->user();
        $updatedAfter = $request->query('updated_after');
        $limit = max(1, min((int) $request->query('limit', 50), 100));

        $convIds = Conversation::whereHas('participants', fn($q) => $q->whereKey($user->id))
            ->pluck('id');

        $query = Message::whereIn('conversation_id', $convIds)
            ->orderBy('updated_at', 'asc');

        if ($updatedAfter) {
            $query->where('updated_at', '>', $updatedAfter);
        }

        $list = $query->limit($limit)->get();

        // mark delivered for messages to the current user
        foreach ($list as $msg) {
            if ($msg->user_id !== $user->id && $msg->delivered_at === null) {
                $msg->delivered_at = now();
                $msg->save();
            }
        }

        $data = $list->map(function ($msg) {
            $status = $msg->read_at ? 'read' : ($msg->delivered_at ? 'delivered' : 'sent');
            return [
                'id' => $msg->id,
                'sender_id' => $msg->user_id,
                'text' => $msg->body,
                'status' => $status,
                'created_at' => $msg->created_at?->toIso8601String(),
                'updated_at' => $msg->updated_at?->toIso8601String(),
                'read_at' => $msg->read_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
            'cursor' => [
                'updated_after' => optional($list->last()?->updated_at)->toIso8601String(),
            ],
        ]);
    }
}