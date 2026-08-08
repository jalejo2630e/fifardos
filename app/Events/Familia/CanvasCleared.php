<?php

namespace App\Events\Familia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class CanvasCleared implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public string $code, public string $from) {}

    public function broadcastOn(): Channel
    {
        return new Channel('family-room.' . $this->code);
    }

    public function broadcastAs(): string
    {
        return 'CanvasCleared';
    }

    public function broadcastWith(): array
    {
        return ['from' => $this->from];
    }
}
