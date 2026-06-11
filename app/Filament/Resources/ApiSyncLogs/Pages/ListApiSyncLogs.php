<?php

namespace App\Filament\Resources\ApiSyncLogs\Pages;

use App\Filament\Resources\ApiSyncLogs\ApiSyncLogResource;
use App\Jobs\SyncFixturesJob;
use App\Jobs\SyncOddsJob;
use App\Jobs\SyncResultsJob;
use App\Models\Tournament;
use App\Services\Scoring\ScoreCalculationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListApiSyncLogs extends ListRecords
{
    protected static string $resource = ApiSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncNow')
                ->label('Sync nu')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Haalt wedstrijden en eindstanden op bij football-data.org.')
                ->action(function () {
                    SyncFixturesJob::dispatch(manual: true);
                    SyncResultsJob::dispatch(manual: true);
                    SyncOddsJob::dispatch(manual: true);

                    Notification::make()
                        ->title('Synchronisatie gestart')
                        ->body('De sync-jobs zijn in de wachtrij geplaatst. Ververs zo de lijst.')
                        ->success()
                        ->send();
                }),
            Action::make('syncOdds')
                ->label('Sync odds')
                ->icon('heroicon-o-currency-euro')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Haalt alleen de odds op bij de odds-provider.')
                ->action(function () {
                    SyncOddsJob::dispatch(manual: true);

                    Notification::make()
                        ->title('Odds-synchronisatie gestart')
                        ->body('De odds-sync is in de wachtrij geplaatst. Ververs zo de lijst.')
                        ->success()
                        ->send();
                }),
            Action::make('recalculateScores')
                ->label('Scores herberekenen')
                ->icon('heroicon-o-calculator')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Berekent de punten van álle voltooide wedstrijden opnieuw op basis van de huidige eindstanden. Handig na een gecorrigeerde uitslag.')
                ->action(function () {
                    $tournament = Tournament::where('is_active', true)->first();

                    if (! $tournament) {
                        Notification::make()
                            ->title('Geen actief toernooi')
                            ->warning()
                            ->send();

                        return;
                    }

                    $scored = app(ScoreCalculationService::class)->recalculateMatches($tournament);

                    Notification::make()
                        ->title('Scores herberekend')
                        ->body("{$scored} voltooide wedstrijd(en) opnieuw gescoord.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
