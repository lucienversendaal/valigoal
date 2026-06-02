<?php

namespace App\Filament\Resources\HistoricalFacts\Pages;

use App\Filament\Resources\HistoricalFacts\HistoricalFactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHistoricalFact extends CreateRecord
{
    protected static string $resource = HistoricalFactResource::class;
}
