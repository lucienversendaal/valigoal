<?php

namespace App\Filament\Resources\BonusPredictions\Schemas;

use App\Enums\BonusType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BonusPredictionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('tournament_id')
                    ->relationship('tournament', 'name')
                    ->required(),
                Select::make('type')
                    ->options(BonusType::class)
                    ->required(),
                Select::make('team_id')
                    ->relationship('team', 'name'),
                TextInput::make('player_name'),
                TextInput::make('points')
                    ->numeric(),
                Toggle::make('is_correct')
                    ->required(),
            ]);
    }
}
