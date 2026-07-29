<?php

namespace App\Console\Commands;

use App\Mail\TournamentReminderMail;
use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTournamentReminders extends Command
{
    protected $signature = 'tournaments:send-reminders';

    protected $description = 'Envía los recordatorios por email de torneos cuya fecha de recordatorio ya llegó';

    public function handle(): int
    {
        $due = Tournament::query()
            ->whereNotNull('reminder_at')
            ->whereNull('reminder_sent_at')
            ->where('reminder_at', '<=', now())
            ->where('status', '!=', 'completed')
            ->with('user')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No hay recordatorios pendientes.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($due as $tournament) {
            $email = $tournament->reminder_email ?: optional($tournament->user)->email;

            if (! $email) {
                $this->warn("Torneo #{$tournament->id} sin email de destino. Se omite.");
                $tournament->update(['reminder_sent_at' => now()]);
                continue;
            }

            try {
                Mail::to($email)->send(new TournamentReminderMail($tournament));
                $tournament->update(['reminder_sent_at' => now()]);
                $sent++;
                $this->line("✔ Recordatorio enviado a {$email} (torneo «{$tournament->name}»)");
            } catch (\Throwable $e) {
                // Marca como intentado para no reintentar cada minuto (evita tormenta de
                // reintentos y posibles envíos duplicados si el proveedor se recupera).
                $tournament->update(['reminder_sent_at' => now()]);
                \Illuminate\Support\Facades\Log::warning('Fallo al enviar recordatorio de torneo', [
                    'tournament_id' => $tournament->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Error enviando torneo #{$tournament->id}: {$e->getMessage()}");
            }
        }

        $this->info("Listo. {$sent} recordatorio(s) enviado(s).");

        return self::SUCCESS;
    }
}
