<?php

namespace App\Console\Commands;

use App\Models\Prediction;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\KnockoutRoundOpen;
use App\Notifications\KnockoutRoundReminder;
use App\Support\KnockoutSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SendPredictionReminders extends Command
{
    protected $signature = 'predictions:remind';

    protected $description = 'Kondigt nieuwe knockoutrondes aan en herinnert deelnemers vlak voor het sluiten van een ronde';

    public function handle(): int
    {
        $tournament = Tournament::current();

        if (! $tournament) {
            $this->info('Geen toernooi gevonden.');

            return self::SUCCESS;
        }

        /** @var array<int, int> $leads */
        $leads = config('services.predictions.reminder_lead_hours', [24, 2]);

        /** @var Collection<int, User> $participants */
        $participants = User::query()
            ->whereNull('blocked_at')
            ->whereNotNull('email_verified_at')
            ->get();

        $announced = $this->announceOpenRounds($tournament, $participants);
        $reminded = $this->sendDeadlineReminders($tournament, $participants, $leads);

        $this->info("{$announced} ronde-aankondiging(en) en {$reminded} herinnering(en) verstuurd.");

        return self::SUCCESS;
    }

    /**
     * Mail every participant once when a knockout round opens, i.e. as soon as
     * the previous round has been fully played.
     *
     * @param  Collection<int, User>  $participants
     */
    private function announceOpenRounds(Tournament $tournament, Collection $participants): int
    {
        $sent = 0;

        foreach (KnockoutSchema::knockoutStages() as $stage) {
            $predecessor = KnockoutSchema::predecessor($stage);

            if ($predecessor === null || ! $tournament->roundIsFullyPlayed($predecessor)) {
                continue;
            }

            $deadline = $tournament->roundDeadline($stage);

            // No fixtures yet, or the round already started — nothing to open.
            if ($deadline === null || $deadline->isPast()) {
                continue;
            }

            if (! Cache::add("round-open:{$tournament->id}:{$stage}", true, now()->addDays(120))) {
                continue; // already announced
            }

            foreach ($participants as $participant) {
                $participant->notify(new KnockoutRoundOpen($stage, $deadline));
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Remind participants who still have open predictions in a round, at each
     * configured lead time before that round's deadline (its first kickoff).
     *
     * @param  Collection<int, User>  $participants
     * @param  array<int, int>  $leads
     */
    private function sendDeadlineReminders(Tournament $tournament, Collection $participants, array $leads): int
    {
        $sent = 0;

        foreach (KnockoutSchema::reminderStages() as $stage) {
            $predecessor = KnockoutSchema::predecessor($stage);

            // A knockout round is only open once its predecessor has been played.
            if ($predecessor !== null && ! $tournament->roundIsFullyPlayed($predecessor)) {
                continue;
            }

            $deadline = $tournament->roundDeadline($stage);

            if ($deadline === null || $deadline->isPast()) {
                continue;
            }

            $activeLeads = array_filter($leads, fn (int $lead) => now()->gte($deadline->copy()->subHours($lead)));

            if ($activeLeads === []) {
                continue; // no lead window reached yet
            }

            $openByUser = $this->openMatchesByUser($tournament, $stage, $participants);

            foreach ($activeLeads as $lead) {
                foreach ($participants as $participant) {
                    $open = $openByUser[$participant->id] ?? 0;

                    if ($open === 0) {
                        continue;
                    }

                    $key = "round-remind:{$tournament->id}:{$stage}:{$participant->id}:{$lead}";

                    if (Cache::add($key, true, now()->addDays(120))) {
                        $participant->notify(new KnockoutRoundReminder($stage, $lead, $open, $deadline));
                        $sent++;
                    }
                }
            }
        }

        return $sent;
    }

    /**
     * Number of still-unpredicted matches per participant for a round, counting
     * only fixtures whose teams are already known.
     *
     * @param  Collection<int, User>  $participants
     * @return array<int, int>
     */
    private function openMatchesByUser(Tournament $tournament, string $stage, Collection $participants): array
    {
        $matchIds = $tournament->matches()
            ->where('stage', $stage)
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->whereNotNull('kickoff_at')
            ->pluck('id');

        if ($matchIds->isEmpty()) {
            return [];
        }

        $total = $matchIds->count();

        $predicted = Prediction::query()
            ->whereIn('match_id', $matchIds)
            ->selectRaw('user_id, count(*) as made')
            ->groupBy('user_id')
            ->pluck('made', 'user_id');

        $open = [];

        foreach ($participants as $participant) {
            $open[$participant->id] = max(0, $total - (int) ($predicted[$participant->id] ?? 0));
        }

        return $open;
    }
}
