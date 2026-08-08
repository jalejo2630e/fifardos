<?php

namespace App\Events\Familia;

use App\Models\FamilyRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class RoomUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public array $snapshot;
    public string $code;

    public function __construct(FamilyRoom $room)
    {
        $this->code = $room->code;
        $this->snapshot = $room->publicSnapshot();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('family-room.' . $this->code);
    }

    public function broadcastAs(): string
    {
        return 'RoomUpdated';
    }

    public function broadcastWith(): array
    {
        return ['room' => $this->snapshot];
    }
}
