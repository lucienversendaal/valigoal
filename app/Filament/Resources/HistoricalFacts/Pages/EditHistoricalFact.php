<?php

namespace App\Filament\Resources\HistoricalFacts\Pages;

use App\Filament\Resources\HistoricalFacts\HistoricalFactResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHistoricalFact extends EditRecord
{
    protected static string $resource = HistoricalFactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
