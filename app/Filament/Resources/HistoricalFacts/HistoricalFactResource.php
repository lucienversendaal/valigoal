<?php

namespace App\Filament\Resources\HistoricalFacts;

use App\Filament\Resources\HistoricalFacts\Pages\CreateHistoricalFact;
use App\Filament\Resources\HistoricalFacts\Pages\EditHistoricalFact;
use App\Filament\Resources\HistoricalFacts\Pages\ListHistoricalFacts;
use App\Filament\Resources\HistoricalFacts\Schemas\HistoricalFactForm;
use App\Filament\Resources\HistoricalFacts\Tables\HistoricalFactsTable;
use App\Models\HistoricalFact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HistoricalFactResource extends Resource
{
    protected static ?string $model = HistoricalFact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return HistoricalFactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HistoricalFactsTable::configure($table);
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
            'index' => ListHistoricalFacts::route('/'),
            'create' => CreateHistoricalFact::route('/create'),
            'edit' => EditHistoricalFact::route('/{record}/edit'),
        ];
    }
}
