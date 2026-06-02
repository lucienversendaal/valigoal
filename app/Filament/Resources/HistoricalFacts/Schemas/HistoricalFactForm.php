<?php

namespace App\Filament\Resources\HistoricalFacts\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HistoricalFactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('subtitle'),
                TextInput::make('year')
                    ->numeric(),
                Textarea::make('body')
                    ->columnSpanFull(),
                TextInput::make('meta'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
