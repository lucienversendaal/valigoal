<?php

namespace App\Filament\Resources\BonusPredictions\Pages;

use App\Filament\Resources\BonusPredictions\BonusPredictionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBonusPrediction extends EditRecord
{
    protected static string $resource = BonusPredictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
