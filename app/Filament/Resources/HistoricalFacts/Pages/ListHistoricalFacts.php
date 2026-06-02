<?php

namespace App\Filament\Resources\HistoricalFacts\Pages;

use App\Filament\Resources\HistoricalFacts\HistoricalFactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHistoricalFacts extends ListRecords
{
    protected static string $resource = HistoricalFactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
