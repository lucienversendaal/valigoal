<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Standing extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'played' => 'integer',
            'won' => 'integer',
            'draw' => 'integer',
            'lost' => 'integer',
            'goals_for' => 'integer',
            'goals_against' => 'integer',
            'goal_difference' => 'integer',
            'points' => 'integer',
        ];
    }

    public function groupLetter(): ?string
    {
        $letters = preg_replace('/[^A-Za-z]/', '', (string) $this->group);

        return $letters !== '' ? strtoupper(substr($letters, -1)) : null;
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
