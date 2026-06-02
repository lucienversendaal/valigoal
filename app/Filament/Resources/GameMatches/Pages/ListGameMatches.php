<?php

namespace App\Filament\Resources\GameMatches\Pages;

use App\Filament\Resources\GameMatches\GameMatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGameMatches extends ListRecords
{
    protected static string $resource = GameMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
