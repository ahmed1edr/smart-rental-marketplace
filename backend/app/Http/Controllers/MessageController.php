<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    // Get list of users the current user has conversations with
    public function getConversations()
    {
        $userId = auth('api')->id();

        // Subquery to get the latest message per conversation
        $latestMessages = DB::table('messages')
            ->select(DB::raw('LEAST(sender_id, receiver_id) as user1'), DB::raw('GREATEST(sender_id, receiver_id) as user2'), DB::raw('MAX(id) as max_id'))
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->groupBy('user1', 'user2');

        $conversations = Message::joinSub($latestMessages, 'latest_messages', function ($join) {
                $join->on('messages.id', '=', 'latest_messages.max_id');
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($message) use ($userId) {
                $otherUser = $message->sender_id === $userId ? $message->receiver : $message->sender;
                $unreadCount = Message::where('sender_id', $otherUser->id)
                                      ->where('receiver_id', $userId)
                                      ->where('is_read', false)
                                      ->count();

                return [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'role' => $otherUser->role,
                    'latest_message' => $message->content,
                    'latest_message_time' => $message->created_at,
                    'unread_count' => $unreadCount,
                ];
            });

        return response()->json($conversations);
    }

    // Get messages between auth user and a specific user
    public function getMessages($otherUserId)
    {
        $userId = auth('api')->id();

        $messages = Message::where(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $userId)->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    // Send a message
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string'
        ]);

        $message = Message::create([
            'sender_id' => auth('api')->id(),
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'is_read' => false,
        ]);

        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    // Mark messages as read
    public function markAsRead($senderId)
    {
        $userId = auth('api')->id();

        Message::where('sender_id', $senderId)
               ->where('receiver_id', $userId)
               ->update(['is_read' => true]);

        return response()->json(['status' => 'success']);
    }
}
