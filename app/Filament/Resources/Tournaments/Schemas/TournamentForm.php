<?php

namespace App\Filament\Resources\Tournaments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TournamentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('external_id')
                    ->numeric(),
                TextInput::make('code')
                    ->required()
                    ->default('WC'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('season'),
                TextInput::make('emblem_url')
                    ->url(),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                TextInput::make('winner_team_id')
                    ->numeric(),
                TextInput::make('runner_up_team_id')
                    ->numeric(),
                TextInput::make('top_scorer_name'),
                TextInput::make('top_scorer_goals')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
