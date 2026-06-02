<?php

namespace App\Filament\Resources\BonusPredictions;

use App\Filament\Resources\BonusPredictions\Pages\CreateBonusPrediction;
use App\Filament\Resources\BonusPredictions\Pages\EditBonusPrediction;
use App\Filament\Resources\BonusPredictions\Pages\ListBonusPredictions;
use App\Filament\Resources\BonusPredictions\Schemas\BonusPredictionForm;
use App\Filament\Resources\BonusPredictions\Tables\BonusPredictionsTable;
use App\Models\BonusPrediction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BonusPredictionResource extends Resource
{
    protected static ?string $model = BonusPrediction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BonusPredictionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BonusPredictionsTable::configure($table);
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
            'index' => ListBonusPredictions::route('/'),
            'create' => CreateBonusPrediction::route('/create'),
            'edit' => EditBonusPrediction::route('/{record}/edit'),
        ];
    }
}
