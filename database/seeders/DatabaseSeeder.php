<?php

namespace Database\Seeders;

use App\Models\Tournament;
use App\Models\Player;
use App\Models\GameMatch;
use App\Models\User;
use App\Services\StandingsService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SportRulesSeeder::class);
        // Admin: credenciales desde variables de entorno; si no hay password,
        // se genera uno aleatorio fuerte y se imprime una vez (NUNCA un default débil).
        // forceCreate (sin factory) para no depender de faker (require-dev, ausente con --no-dev).
        // getenv() para leer aunque la config esté cacheada en producción.
        $adminEmail = getenv('SEEDER_ADMIN_EMAIL') ?: 'admin@fifardos.com';
        $adminName = getenv('SEEDER_ADMIN_NAME') ?: 'Admin';
        $adminPassword = getenv('SEEDER_ADMIN_PASSWORD') ?: null;
        $generated = false;
        if (! $adminPassword) {
            $adminPassword = \Illuminate\Support\Str::password(16);
            $generated = true;
        }

        $user = User::where('email', $adminEmail)->first();
        if (! $user) {
            $user = User::forceCreate([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => $adminPassword, // el cast 'hashed' lo hashea
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
            if ($generated && isset($this->command)) {
                $this->command->warn("Admin creado: {$adminEmail} / {$adminPassword}");
                $this->command->warn('⚠ Guardá esta contraseña: no se volverá a mostrar.');
            }
        }

        $this->createTorneoDiego($user);
        $this->createTorneoRelampago($user);
        $this->createCopaEliminatorias($user);
    }

    private function createTorneoDiego(User $user): void
    {
        $t = Tournament::create([
            'user_id' => $user->id,
            'name' => 'Torneo FIFA - Grupo de Diego',
            'consoles_count' => 2,
            'status' => 'in_progress',
            'color' => '#F97316',
        ]);

        $names = ['Diego', 'Julian', 'Javier', 'Sebas', 'Cristian', 'Martin', 'Lucas', 'Franco'];
        $ids = $this->createPlayers($t, $names);
        $this->generateGroupMatches($t, $ids);

        $scores = [
            [3,1],[2,2],[4,0],[1,3],
            [2,0],[1,1],[3,2],[0,3],
        ];
        $this->finishMatches($t, 2, $scores);
    }

    private function createTorneoRelampago(User $user): void
    {
        $t = Tournament::create([
            'user_id' => $user->id,
            'name' => 'Torneo Relámpago',
            'consoles_count' => 3,
            'status' => 'in_progress',
            'color' => '#8B5CF6',
        ]);

        $names = ['Alex','Bryan','Carlos','Daniel','Eduardo','Fernando','Gabriel','Hugo',
                   'Ivan','Jorge','Kevin','Luis','Miguel','Nicolas','Omar','Pablo'];
        $ids = $this->createPlayers($t, $names);
        $this->generateGroupMatches($t, $ids);

        // Finish first 3 rounds with random scores
        for ($r = 1; $r <= 3; $r++) {
            foreach ($t->matches()->where('round', $r)->get() as $m) {
                $m->update([
                    'score1' => rand(0, 5),
                    'score2' => rand(0, 5),
                    'status' => 'finished',
                    'played_at' => now()->subDays(4 - $r),
                ]);
            }
        }
    }

    private function createCopaEliminatorias(User $user): void
    {
        $t = Tournament::create([
            'user_id' => $user->id,
            'name' => 'Copa Eliminatorias',
            'consoles_count' => 2,
            'status' => 'in_progress',
            'color' => '#10B981',
        ]);

        $names = ['Diego', 'Julian', 'Javier', 'Sebas', 'Cristian', 'Martin', 'Lucas', 'Franco'];
        $ids = $this->createPlayers($t, $names);
        $this->generateGroupMatches($t, $ids);

        $groupScores = [
            [3,1],[2,2],[4,0],[1,3],
            [2,0],[1,1],[3,2],[0,3],
            [4,1],[2,3],[1,0],[3,3],
            [1,2],[3,0],[2,2],[4,1],
            [2,1],[0,4],[3,3],[1,2],
            [4,2],[1,3],[2,0],[3,1],
            [2,2],[3,1],[0,2],[4,0],
        ];
        $this->finishMatches($t, 7, $groupScores);

        $standings = app(StandingsService::class)->calculate($t);
        $top = array_slice($standings, 0, 4);
        $ids_top = array_map(fn($p) => $p['player_id'], $top);
        $maxRound = $t->matches()->where('phase', 'group')->max('round') ?? 0;

        GameMatch::create([
            'tournament_id' => $t->id, 'round' => $maxRound + 1,
            'player1_id' => $ids_top[0], 'player2_id' => $ids_top[3],
            'phase' => 'semifinals', 'bracket_position' => 'sf_1',
            'status' => 'finished', 'score1' => 2, 'score2' => 1,
            'tv_number' => 1, 'played_at' => now()->subHours(2),
        ]);
        GameMatch::create([
            'tournament_id' => $t->id, 'round' => $maxRound + 1,
            'player1_id' => $ids_top[1], 'player2_id' => $ids_top[2],
            'phase' => 'semifinals', 'bracket_position' => 'sf_2',
            'status' => 'finished', 'score1' => 3, 'score2' => 3,
            'tv_number' => 2, 'played_at' => now()->subHours(1),
        ]);

        $finalista1 = $ids_top[0];
        $finalista2 = $ids_top[1];
        $perdedor1 = $ids_top[3];
        $perdedor2 = $ids_top[2];

        GameMatch::create([
            'tournament_id' => $t->id, 'round' => $maxRound + 2,
            'player1_id' => $perdedor1, 'player2_id' => $perdedor2,
            'phase' => 'third_place', 'bracket_position' => 'third_place',
            'status' => 'pending', 'tv_number' => 1,
        ]);

        GameMatch::create([
            'tournament_id' => $t->id, 'round' => $maxRound + 3,
            'player1_id' => $finalista1, 'player2_id' => $finalista2,
            'phase' => 'final', 'bracket_position' => 'final',
            'status' => 'pending', 'tv_number' => 1,
        ]);
    }

    private function createPlayers(Tournament $t, array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            $p = Player::create(['tournament_id' => $t->id, 'name' => $name]);
            $ids[$name] = $p->id;
        }
        return $ids;
    }

    private function generateGroupMatches(Tournament $t, array $playerIds): void
    {
        $names = array_keys($playerIds);
        $ids = array_values($playerIds);
        $num = count($names);
        $rounds = $num - 1;
        $half = intdiv($num, 2);
        $tvCount = $t->consoles_count;

        for ($round = 0; $round < $rounds; $round++) {
            for ($i = 0; $i < $half; $i++) {
                GameMatch::create([
                    'tournament_id' => $t->id,
                    'round' => $round + 1,
                    'player1_id' => $ids[$i],
                    'player2_id' => $ids[$num - 1 - $i],
                    'phase' => 'group',
                    'status' => 'pending',
                    'tv_number' => ($i % $tvCount) + 1,
                ]);
            }
            $last = array_pop($names);
            $lastId = array_pop($ids);
            array_splice($names, 1, 0, [$last]);
            array_splice($ids, 1, 0, [$lastId]);
        }
    }

    private function finishMatches(Tournament $t, int $rounds, array $scores): void
    {
        $idx = 0;
        for ($r = 1; $r <= $rounds; $r++) {
            foreach ($t->matches()->where('round', $r)->get() as $m) {
                if (isset($scores[$idx])) {
                    $m->update([
                        'score1' => $scores[$idx][0],
                        'score2' => $scores[$idx][1],
                        'status' => 'finished',
                        'played_at' => now()->subDays(7 - $r),
                    ]);
                }
                $idx++;
            }
        }
    }
}
