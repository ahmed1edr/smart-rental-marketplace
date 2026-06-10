<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Get user's notifications
     */
    public function index()
    {
        $user = auth('api')->user();
        
        $notifications = $user->notifications()
                              ->orderBy('created_at', 'desc')
                              ->take(20)
                              ->get();

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($id)
    {
        $user = auth('api')->user();
        
        $notification = Notification::where('user_id', $user->id)
                                    ->where('id', $id)
                                    ->firstOrFail();
                                    
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification lue']);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = auth('api')->user();
        
        $user->notifications()->update(['is_read' => true]);

        return response()->json(['message' => 'Toutes les notifications ont été lues']);
    }
}
