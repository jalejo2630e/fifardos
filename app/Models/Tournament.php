<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tournament extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'sport',
        'mode',
        'consoles_count',
        'minutes_per_match',
        'status',
        'format',
        'home_and_away',
        'color',
        'max_players',
        'finished_at',
        'reminder_at',
        'reminder_email',
        'reminder_sent_at',
    ];

    protected $casts = [
        'home_and_away' => 'boolean',
        'finished_at' => 'datetime',
        'reminder_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Genera un slug legible y único al crear el torneo (para URLs públicas
        // tipo /torneos/apertura-2026/bracket). No se regenera al renombrar para
        // no romper enlaces ya compartidos/indexados.
        static::creating(function (Tournament $tournament) {
            if (blank($tournament->slug)) {
                $tournament->slug = static::generateUniqueSlug($tournament->name);
            }
        });
    }

    /**
     * Devuelve un slug único a partir del nombre. Evita slugs vacíos o puramente
     * numéricos (chocarían con las rutas por id) y resuelve colisiones con sufijo.
     */
    public static function generateUniqueSlug(?string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $name);
        if ($base === '' || ctype_digit($base)) {
            $base = 'torneo';
        }

        $slug = $base;
        $n = 2;
        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    /**
     * Estima la duración total del torneo en minutos: partidos del round-robin
     * (liga o fase de grupos, con ida y vuelta si aplica) + eliminatorias (solo en
     * formato groups_knockout), repartidos en paralelo entre las consolas/canchas.
     */
    public static function estimateMinutes(
        int $players,
        int $consoles,
        int $minutesPerMatch,
        string $format = 'groups_knockout',
        bool $homeAndAway = false,
    ): int {
        if ($players < 2) return 0;

        $consoles = max(1, $consoles);
        $minutesPerMatch = max(1, $minutesPerMatch);

        $group = intdiv($players * ($players - 1), 2);
        if ($homeAndAway) $group *= 2;

        $knockout = 0;
        if ($format === 'groups_knockout') {
            // Réplica del top de eliminatorias (ver TournamentController::autoGenerateKnockout)
            $top = $players <= 4 ? 4 : ($players <= 8 ? 8 : 16);
            $top = min($top, $players);
            $top = (int) pow(2, (int) floor(log($top, 2)));
            if ($top < 2) $top = 2;
            $knockout = $top >= 4 ? $top : 1;
        }

        $total = $group + $knockout;
        $slots = (int) ceil($total / $consoles);

        return $slots * $minutesPerMatch;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(TournamentPrize::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(TournamentRule::class);
    }

    /** Reglas del torneo como mapa clave => valor. */
    public function rulesMap(): array
    {
        return $this->rules->pluck('value', 'rule_key')->all();
    }

    /** ¿Modo de juego virtual (consolas) o en campo físico (canchas)? */
    public function isPhysical(): bool
    {
        return ($this->mode ?? 'virtual') === 'physical';
    }

    /** Etiqueta del espacio de juego: canchas si es físico, consolas si es virtual. */
    public function venueLabel(): string
    {
        return $this->isPhysical() ? 'canchas' : 'consolas';
    }

    /** ¿Este torneo es de deporte de equipo? */
    public function isTeamSport(): bool
    {
        return \App\Services\SportsCatalog::isTeam($this->sport ?? 'fifa');
    }

    /** ¿Este torneo usa marcador por sets? */
    public function isSetsSport(): bool
    {
        return \App\Services\SportsCatalog::isSets($this->sport ?? 'fifa');
    }

    /** Competidores del torneo (equipos si es deporte de equipo, si no jugadores). */
    public function competitors()
    {
        if ($this->isTeamSport()) {
            return $this->teams();
        }
        return $this->players();
    }
}
