<?php

namespace App\Filament\Resources\BonusPredictions\Pages;

use App\Filament\Resources\BonusPredictions\BonusPredictionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBonusPredictions extends ListRecords
{
    protected static string $resource = BonusPredictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
