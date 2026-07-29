<?php

namespace App\Mail;

use App\Models\Tournament;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TournamentCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tournament $tournament)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🏆 Torneo creado: «' . $this->tournament->name . '»',
        );
    }

    public function content(): Content
    {
        $t = $this->tournament->loadCount(['players', 'matches']);

        return new Content(
            view: 'emails.tournament-created',
            with: [
                'tournament'   => $t,
                'playersCount' => $t->players_count ?? 0,
                'matchesCount' => $t->matches_count ?? 0,
                'url'          => route('tournaments.show', $t),
                'reminderAt'   => $t->reminder_at,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
