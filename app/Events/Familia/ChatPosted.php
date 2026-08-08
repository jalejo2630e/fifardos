<?php

namespace App\Events\Familia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ChatPosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param string $kind guess | correct | system
     */
    public function __construct(
        public string $code,
        public string $kind,
        public string $text,
        public ?string $name = null,
        public ?int $memberId = null,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('family-room.' . $this->code);
    }

    public function broadcastAs(): string
    {
        return 'ChatPosted';
    }

    public function broadcastWith(): array
    {
        return [
            'kind' => $this->kind,
            'text' => $this->text,
            'name' => $this->name,
            'member_id' => $this->memberId,
        ];
    }
}
