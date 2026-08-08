<?php

namespace App\Events\Familia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class DrawStroke implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $code,
        public string $from,     // token del dibujante (para que su propio cliente ignore el eco)
        public array $points,    // [{x,y}, ...] normalizados 0..1
        public string $color,
        public float $size,
        public bool $begin = false,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('family-room.' . $this->code);
    }

    public function broadcastAs(): string
    {
        return 'DrawStroke';
    }

    public function broadcastWith(): array
    {
        return [
            'from' => $this->from,
            'points' => $this->points,
            'color' => $this->color,
            'size' => $this->size,
            'begin' => $this->begin,
        ];
    }
}
