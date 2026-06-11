<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\Actions\ScoringActions;
use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTournament extends EditRecord
{
    protected static string $resource = TournamentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...ScoringActions::all(),
            DeleteAction::make(),
        ];
    }
}
