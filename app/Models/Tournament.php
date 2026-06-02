<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'top_scorer_goals' => 'integer',
        ];
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function bonusPredictions(): HasMany
    {
        return $this->hasMany(BonusPrediction::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function runnerUp(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'runner_up_team_id');
    }

    public function bonusesAreLocked(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isPast();
    }

    /**
     * The tournament the app should currently point at: active first,
     * then the most recently synced.
     */
    public static function current(): ?self
    {
        return static::query()
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first();
    }
}
