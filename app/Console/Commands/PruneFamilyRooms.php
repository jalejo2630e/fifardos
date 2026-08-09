<?php

namespace App\Console\Commands;

use App\Models\FamilyRoom;
use Illuminate\Console\Command;

class PruneFamilyRooms extends Command
{
    protected $signature = 'familia:prune';

    protected $description = 'Elimina las salas de minijuegos donde nadie estuvo presente hace rato (libera la DB).';

    public function handle(): int
    {
        $cutoff = now()->subHours((int) config('familia.prune_hours', 2));

        // Salas sin ningún participante visto recientemente (incluye salas vacías).
        // Al borrar la sala, la FK con cascade elimina también a sus participantes.
        $count = 0;
        FamilyRoom::whereDoesntHave('members', fn ($q) => $q->where('last_seen_at', '>=', $cutoff))
            ->chunkById(100, function ($rooms) use (&$count) {
                foreach ($rooms as $room) {
                    $room->delete();
                    $count++;
                }
            });

        $this->info("Salas de minijuegos eliminadas: {$count}");

        return self::SUCCESS;
    }
}
