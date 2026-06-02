<?php

namespace App\Filament\Resources\ApiSyncLogs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ApiSyncLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('success'),
                TextInput::make('endpoint'),
                TextInput::make('items_processed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('message')
                    ->columnSpanFull(),
                TextInput::make('context'),
                TextInput::make('duration_ms')
                    ->numeric(),
                Toggle::make('triggered_manually')
                    ->required(),
            ]);
    }
}
