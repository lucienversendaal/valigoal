<?php

namespace App\Console\Commands;

use App\Models\GameMatch;
use App\Models\User;
use App\Notifications\PredictionReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendPredictionReminders extends Command
{
    protected $signature = 'predictions:remind';

    protected $description = 'Mail deelnemers die nog geen voorspelling hebben ingevuld vlak voor de deadline';

    public function handle(): int
    {
        /** @var array<int, int> $leads */
        $leads = config('services.predictions.reminder_lead_hours', [24, 2]);

        $matches = GameMatch::query()
            ->whereNotNull('kickoff_at')
            ->where('kickoff_at', '>', now())
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        $participants = User::query()
            ->whereNull('blocked_at')
            ->whereNotNull('email_verified_at')
            ->get();

        $sent = 0;

        foreach ($matches as $match) {
            foreach ($leads as $lead) {
                if (now()->lt($match->kickoff_at->copy()->subHours($lead))) {
                    continue; // lead window not reached yet
                }

                $predictedUserIds = $match->predictions()->pluck('user_id')->all();

                foreach ($participants as $participant) {
                    if (in_array($participant->id, $predictedUserIds, true)) {
                        continue;
                    }

                    $key = "reminder:{$match->id}:{$participant->id}:{$lead}";

                    if (Cache::add($key, true, now()->addDays(60))) {
                        $participant->notify(new PredictionReminder($match, $lead));
                        $sent++;
                    }
                }
            }
        }

        $this->info("{$sent} herinnering(en) verstuurd.");

        return self::SUCCESS;
    }
}
