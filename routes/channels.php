<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('conversations.{conversationId}', function ($user, int $conversationId) {
    return Conversation::whereKey($conversationId)
        ->whereHas('participants', fn ($q) => $q->whereKey($user->id))
        ->exists();
});
