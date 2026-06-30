<?php

namespace App\Filament\Resources\GameMatches\Schemas;

use App\Enums\MatchStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GameMatchForm
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
                Select::make('home_team_id')
                    ->relationship('homeTeam', 'name'),
                Select::make('away_team_id')
                    ->relationship('awayTeam', 'name'),
                TextInput::make('stage'),
                TextInput::make('group'),
                TextInput::make('matchday')
                    ->numeric(),
                DateTimePicker::make('kickoff_at'),
                Select::make('status')
                    ->options(MatchStatus::class)
                    ->default('SCHEDULED')
                    ->required(),
                TextInput::make('home_score')
                    ->numeric(),
                TextInput::make('away_score')
                    ->numeric()
                    ->helperText('Stand na 90 minuten (regulier). Voorspellingen worden hiertegen afgerekend.'),
                Select::make('decided_by')
                    ->label('Beslist door')
                    ->options([
                        'REGULAR' => 'Reguliere tijd',
                        'EXTRA_TIME' => 'Verlenging',
                        'PENALTIES' => 'Strafschoppen',
                    ])
                    ->native(false),
                TextInput::make('penalty_home_score')
                    ->label('Strafschoppen thuis')
                    ->numeric(),
                TextInput::make('penalty_away_score')
                    ->label('Strafschoppen uit')
                    ->numeric(),
                TextInput::make('winner')
                    ->helperText('Doorgestoten ploeg: HOME_TEAM of AWAY_TEAM (na verlenging/strafschoppen), leeg bij gelijkspel.'),
                DateTimePicker::make('finished_at'),
                DateTimePicker::make('last_synced_at'),
                Toggle::make('points_awarded')
                    ->required(),
                Toggle::make('result_locked')
                    ->label('Resultaat vergrendeld')
                    ->helperText('Aan: de synchronisatie overschrijft de score/uitslag van deze wedstrijd niet meer. Wordt automatisch aangezet zodra je de score handmatig wijzigt.'),
            ]);
    }
}
