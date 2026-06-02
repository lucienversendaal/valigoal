<?php

namespace App\Filament\Resources\Teams\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tournament_id')
                    ->relationship('tournament', 'name')
                    ->required(),
                TextInput::make('external_id')
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('short_name'),
                TextInput::make('tla'),
                TextInput::make('crest_url')
                    ->url(),
                TextInput::make('group'),
            ]);
    }
}
