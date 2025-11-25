<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\ShipmentMessage;
use App\Models\ShipmentMessageRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShipmentMessageController extends Controller
{
    private function ensureAuthorized(Shipment $shipment): void
    {
        $userId = Auth::id();
        $isRelated = ($shipment->pic_user_id === $userId) || ($shipment->approved_by_user_id === $userId) || Auth::user()->role_id == 1;

        if (! $isRelated) {
            abort(403, 'You are not authorized to access messages for this shipment');
        }
    }


    public function index(Request $request, Shipment $shipment)
    {
        $this->ensureAuthorized($shipment);

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'integer', 'min:1'],
        ]);

        $limit = (int)($validated['limit'] ?? 20);
        $cursor = $validated['cursor'] ?? null;

        $query = ShipmentMessage::with('sender')
            ->where('shipment_id', $shipment->id);

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        // Ambil pesan terbaru lalu urutkan ASC untuk tampilan
        $messages = $query->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        // Hitung status berdasarkan posisi baca current user
        $read = ShipmentMessageRead::where('shipment_id', $shipment->id)
            ->where('user_id', Auth::id())
            ->first();
        $lastReadId = $read?->last_read_id ?? 0;

        $data = $messages->map(function (ShipmentMessage $m) use ($lastReadId) {
            $status = ($m->sender_id !== Auth::id() && $lastReadId >= $m->id) ? 'seen' : 'delivered';
            return [
                'id' => $m->id,
                'shipment_id' => $m->shipment_id,
                'sender' => [
                    'id' => optional($m->sender)->id,
                    'name' => optional($m->sender)->name,
                ],
                'text' => $m->text,
                'status' => $status,
                'created_at' => $m->created_at?->toIso8601String(),
            ];
        });

        $hasMore = false;
        $nextCursor = null;
        if ($messages->count() > 0) {
            $oldestId = $messages->first()->id;
            $hasMore = ShipmentMessage::where('shipment_id', $shipment->id)
                ->where('id', '<', $oldestId)
                ->exists();
            $nextCursor = $hasMore ? $oldestId : null;
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
                'limit' => $limit,
            ],
        ]);
    }

    public function store(Request $request, Shipment $shipment)
    {
        $this->ensureAuthorized($shipment);

        $validated = $request->validate([
            'text' => ['required', 'string', 'min:1'],
            // 'attachments' => ['nullable', 'array'], // future
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $message = ShipmentMessage::create([
            'shipment_id' => $shipment->id,
            'sender_id' => $user->id,
            'text' => $validated['text'],
        ]);

        $message->load('sender');

        return response()->json([
            'data' => [
                'id' => $message->id,
                'shipment_id' => $message->shipment_id,
                'sender' => [
                    'id' => optional($message->sender)->id,
                    'name' => optional($message->sender)->name,
                ],
                'text' => $message->text,
                'status' => 'delivered',
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function markRead(Request $request, Shipment $shipment)
    {
        $this->ensureAuthorized($shipment);

        $validated = $request->validate([
            'last_read_id' => ['required', 'integer', 'min:1'],
        ]);

        $lastReadId = (int) $validated['last_read_id'];

        $existsInShipment = ShipmentMessage::where('shipment_id', $shipment->id)
            ->where('id', $lastReadId)
            ->exists();
        if (! $existsInShipment) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'last_read_id' => ['last_read_id is not valid for the given shipment'],
                ],
            ], 400);
        }

        $record = ShipmentMessageRead::firstOrNew([
            'shipment_id' => $shipment->id,
            'user_id' => Auth::id(),
        ]);

        // Jangan mundur dari posisi baca yang sudah lebih tinggi
        $record->last_read_id = max((int)($record->last_read_id ?? 0), $lastReadId);
        $record->save();

        return response()->json([
            'data' => [
                'shipment_id' => $shipment->id,
                'last_read_id' => $record->last_read_id,
            ],
        ]);
    }

    public function stream(Request $request, Shipment $shipment)
    {
        $this->ensureAuthorized($shipment);

        $since = (int) ($request->query('since_id', $request->header('Last-Event-ID', 0)));
        $timeoutSeconds = 30; // durasi koneksi sebelum client reconnect

        $response = response()->stream(function () use ($shipment, $since, $timeoutSeconds) {
            // interval reconnect default 3 detik
            echo "retry: 3000\n\n";

            $lastId = $since;
            $start = microtime(true);

            while ((microtime(true) - $start) < $timeoutSeconds) {
                $messages = ShipmentMessage::with('sender')
                    ->where('shipment_id', $shipment->id)
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->get();

                foreach ($messages as $m) {
                    $payload = json_encode([
                        'id' => $m->id,
                        'shipment_id' => $m->shipment_id,
                        'sender' => [
                            'id' => optional($m->sender)->id,
                            'name' => optional($m->sender)->name,
                        ],
                        'text' => $m->text,
                        'status' => 'delivered',
                        'created_at' => $m->created_at?->toIso8601String(),
                    ], JSON_UNESCAPED_UNICODE);

                    echo "id: {$m->id}\n";
                    echo "event: message\n";
                    echo "data: {$payload}\n\n";
                    $lastId = $m->id;
                }

                // heartbeat untuk menjaga koneksi tetap hidup
                echo ": heartbeat\n\n";

                @ob_flush();
                @flush();
                usleep(800000); // 0.8s
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);

        return $response;
    }
}