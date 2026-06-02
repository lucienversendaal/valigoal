<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->color(fn ($state) => $state === UserRole::SuperAdmin ? 'primary' : 'gray')
                    ->searchable(),
                IconColumn::make('blocked_at')
                    ->label('Geblokkeerd')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
                TextColumn::make('email_verified_at')
                    ->label('Geverifieerd')
                    ->dateTime('d-m-Y')
                    ->placeholder('Niet geverifieerd')
                    ->sortable(),
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
                Action::make('toggleBlock')
                    ->label(fn (User $record) => $record->isBlocked() ? 'Deblokkeren' : 'Blokkeren')
                    ->icon(fn (User $record) => $record->isBlocked() ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (User $record) => $record->isBlocked() ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => ! $record->isSuperAdmin())
                    ->action(fn (User $record) => $record->forceFill([
                        'blocked_at' => $record->isBlocked() ? null : now(),
                    ])->save()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
