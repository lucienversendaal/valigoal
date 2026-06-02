<?php

namespace App\Filament\Resources\ApiSyncLogs;

use App\Filament\Resources\ApiSyncLogs\Pages\CreateApiSyncLog;
use App\Filament\Resources\ApiSyncLogs\Pages\EditApiSyncLog;
use App\Filament\Resources\ApiSyncLogs\Pages\ListApiSyncLogs;
use App\Filament\Resources\ApiSyncLogs\Schemas\ApiSyncLogForm;
use App\Filament\Resources\ApiSyncLogs\Tables\ApiSyncLogsTable;
use App\Models\ApiSyncLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ApiSyncLogResource extends Resource
{
    protected static ?string $model = ApiSyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'synclog';

    protected static ?string $pluralModelLabel = 'synclogs';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ApiSyncLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiSyncLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiSyncLogs::route('/'),
            'create' => CreateApiSyncLog::route('/create'),
            'edit' => EditApiSyncLog::route('/{record}/edit'),
        ];
    }
}
