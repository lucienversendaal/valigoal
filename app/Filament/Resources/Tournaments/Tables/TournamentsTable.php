<?php

namespace App\Filament\Resources\Tournaments\Tables;

use App\Filament\Resources\Tournaments\Actions\ScoringActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TournamentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('external_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('season')
                    ->searchable(),
                TextColumn::make('emblem_url')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('winner_team_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('runner_up_team_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('top_scorer_name')
                    ->searchable(),
                TextColumn::make('top_scorer_goals')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
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
                //
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make(ScoringActions::all())
                    ->label('Berekeningen')
                    ->icon('heroicon-o-calculator')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
