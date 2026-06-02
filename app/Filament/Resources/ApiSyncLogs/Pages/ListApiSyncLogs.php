<?php

namespace App\Filament\Resources\ApiSyncLogs\Pages;

use App\Filament\Resources\ApiSyncLogs\ApiSyncLogResource;
use App\Jobs\SyncFixturesJob;
use App\Jobs\SyncOddsJob;
use App\Jobs\SyncResultsJob;
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
        ];
    }
}
