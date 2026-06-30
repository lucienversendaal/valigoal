<?php

namespace App\Filament\Resources\GameMatches\Pages;

use App\Filament\Resources\GameMatches\GameMatchResource;
use App\Jobs\RecalculateScoresJob;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGameMatch extends EditRecord
{
    protected static string $resource = GameMatchResource::class;

    /** Whether the score/winner was changed by hand in this save. */
    protected bool $resultChanged = false;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Lock the result automatically when an admin corrects the score by hand,
     * so the next API sync won't overwrite it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $original = $this->record->only(['home_score', 'away_score', 'winner', 'penalty_home_score', 'penalty_away_score']);

        $this->resultChanged = (int) ($data['home_score'] ?? null) !== (int) ($original['home_score'] ?? null)
            || (int) ($data['away_score'] ?? null) !== (int) ($original['away_score'] ?? null)
            || ($data['winner'] ?? null) !== ($original['winner'] ?? null)
            || (int) ($data['penalty_home_score'] ?? null) !== (int) ($original['penalty_home_score'] ?? null)
            || (int) ($data['penalty_away_score'] ?? null) !== (int) ($original['penalty_away_score'] ?? null);

        if ($this->resultChanged) {
            $data['result_locked'] = true;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // A corrected result invalidates any points already awarded for it, so
        // re-score the tournament (this ignores the points_awarded guard).
        if ($this->resultChanged && $this->record->tournament) {
            RecalculateScoresJob::dispatch($this->record->tournament, manual: true);
        }
    }
}
