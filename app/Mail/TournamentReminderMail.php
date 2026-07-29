<?php

namespace App\Mail;

use App\Models\Tournament;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TournamentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tournament $tournament)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚽ Recordatorio: tu torneo «' . $this->tournament->name . '» te espera',
        );
    }

    public function content(): Content
    {
        $t = $this->tournament->loadCount([
            'players',
            'matches',
            'matches as matches_played' => fn ($q) => $q->where('status', 'finished'),
        ]);

        $pending = max(0, ($t->matches_count ?? 0) - ($t->matches_played ?? 0));

        return new Content(
            view: 'emails.tournament-reminder',
            with: [
                'tournament'  => $t,
                'playersCount' => $t->players_count ?? 0,
                'pendingMatches' => $pending,
                'url' => route('tournaments.show', $t),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
