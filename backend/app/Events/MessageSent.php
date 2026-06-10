<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // Channel ID based on the users involved to keep it private
        $chatId = min($this->message->sender_id, $this->message->receiver_id) . '-' . max($this->message->sender_id, $this->message->receiver_id);
        return [
            new PrivateChannel('chat.' . $chatId),
            new PrivateChannel('App.Models.User.' . $this->message->receiver_id),
            new PrivateChannel('App.Models.User.' . $this->message->sender_id),
        ];
    }
}
