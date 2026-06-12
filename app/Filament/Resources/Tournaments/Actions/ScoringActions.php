<?php

namespace App\Filament\Resources\Tournaments\Actions;

use App\Models\ApiSyncLog;
use App\Models\Tournament;
use App\Services\Scoring\ScoreCalculationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Manual scoring triggers for a tournament, shared between the resource table
 * (row actions) and the edit page (header actions).
 */
class ScoringActions
{
    /**
     * @return array<int, Action>
     */
    public static function all(): array
    {
        return [
            self::recalculateScores(),
            self::awardBonuses(),
        ];
    }

    public static function recalculateScores(): Action
    {
        return Action::make('recalculateScores')
            ->label('Scores herberekenen')
            ->icon('heroicon-o-calculator')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Wedstrijdscores herberekenen')
            ->modalDescription('Berekent de punten van álle voltooide wedstrijden opnieuw op basis van de huidige eindstanden. Handig na een gecorrigeerde uitslag.')
            ->action(function (Action $action) {
                /** @var Tournament $tournament */
                $tournament = $action->getRecord();

                $start = hrtime(true);
                $scored = app(ScoreCalculationService::class)->recalculateMatches($tournament);

                ApiSyncLog::create([
                    'type' => 'scoring',
                    'status' => 'success',
                    'endpoint' => 'recalculateMatches',
                    'items_processed' => $scored,
                    'message' => "{$scored} voltooide wedstrijd(en) opnieuw gescoord voor toernooi \"{$tournament->name}\".",
                    'context' => ['tournament_id' => $tournament->id, 'matches_scored' => $scored],
                    'duration_ms' => (int) ((hrtime(true) - $start) / 1e6),
                    'triggered_manually' => true,
                ]);

                Notification::make()
                    ->title('Scores herberekend')
                    ->body("{$scored} voltooide wedstrijd(en) opnieuw gescoord.")
                    ->success()
                    ->send();
            });
    }

    public static function awardBonuses(): Action
    {
        return Action::make('awardBonuses')
            ->label('Bonussen toekennen')
            ->icon('heroicon-o-trophy')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Bonuspunten toekennen')
            ->modalDescription('Bepaalt de winnaar/runner-up uit de finale en rekent de bonusvoorspellingen (winnaar, finalist, topscorer) na. Vul de topscorer eerst handmatig in bij het toernooi.')
            ->action(function (Action $action) {
                /** @var Tournament $tournament */
                $tournament = $action->getRecord();
                $scoring = app(ScoreCalculationService::class);

                $start = hrtime(true);
                $resolved = $scoring->resolveFinalOutcome($tournament);
                $correct = $scoring->awardBonuses($tournament);

                ApiSyncLog::create([
                    'type' => 'bonuses',
                    'status' => 'success',
                    'endpoint' => 'awardBonuses',
                    'items_processed' => $correct,
                    'message' => "{$correct} bonusvoorspelling(en) goed gerekend voor toernooi \"{$tournament->name}\".",
                    'context' => [
                        'tournament_id' => $tournament->id,
                        'bonuses_correct' => $correct,
                        'winner_resolved' => $resolved,
                        'winner_team_id' => $tournament->winner_team_id,
                    ],
                    'duration_ms' => (int) ((hrtime(true) - $start) / 1e6),
                    'triggered_manually' => true,
                ]);

                $notification = Notification::make()
                    ->title('Bonussen toegekend')
                    ->body("{$correct} bonusvoorspelling(en) goed gerekend.")
                    ->success();

                if (! $tournament->winner_team_id) {
                    $notification
                        ->title('Bonussen toegekend (let op)')
                        ->body("{$correct} bonusvoorspelling(en) goed gerekend. De winnaar kon nog niet uit de finale worden bepaald — winnaar/finalist-bonussen blijven 0 tot de finale is gespeeld.")
                        ->warning();
                } elseif (! $resolved) {
                    $notification->body("{$correct} bonusvoorspelling(en) goed gerekend (winnaar was al ingevuld).");
                }

                $notification->send();
            });
    }
}
