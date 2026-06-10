<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    // chatId is 'min_id-max_id'
    $ids = explode('-', $chatId);
    if (count($ids) !== 2) return false;
    return in_array((string)$user->id, $ids);
});
