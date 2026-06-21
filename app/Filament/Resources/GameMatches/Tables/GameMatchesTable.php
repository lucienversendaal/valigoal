<?php

namespace App\Filament\Resources\GameMatches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GameMatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tournament.name')
                    ->searchable(),
                TextColumn::make('external_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('homeTeam.name')
                    ->searchable(),
                TextColumn::make('awayTeam.name')
                    ->searchable(),
                TextColumn::make('stage')
                    ->searchable(),
                TextColumn::make('group')
                    ->searchable(),
                TextColumn::make('matchday')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('kickoff_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('home_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('away_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('winner')
                    ->searchable(),
                TextColumn::make('finished_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_synced_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('points_awarded')
                    ->boolean(),
                IconColumn::make('result_locked')
                    ->label('Vergrendeld')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('result_locked')
                    ->label('Resultaat vergrendeld'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
