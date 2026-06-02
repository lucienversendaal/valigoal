<?php

namespace App\Filament\Resources\GameMatches\Pages;

use App\Filament\Resources\GameMatches\GameMatchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGameMatch extends CreateRecord
{
    protected static string $resource = GameMatchResource::class;
}
