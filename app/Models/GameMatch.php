<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    /** football-data.org stage value for the group phase. */
    public const GROUP_STAGE = 'GROUP_STAGE';

    protected $table = 'matches';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'kickoff_at' => 'datetime',
            'finished_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'status' => MatchStatus::class,
            'home_score' => 'integer',
            'away_score' => 'integer',
            'matchday' => 'integer',
            'points_awarded' => 'boolean',
            'odds' => 'array',
            'odds_updated_at' => 'datetime',
        ];
    }

    public function hasOdds(): bool
    {
        return isset($this->odds['home'], $this->odds['draw'], $this->odds['away']);
    }

    /**
     * The outcome with the shortest (most likely) odds.
     */
    public function favouriteOutcome(): ?string
    {
        if (! $this->hasOdds()) {
            return null;
        }

        $odds = ['HOME_TEAM' => $this->odds['home'], 'DRAW' => $this->odds['draw'], 'AWAY_TEAM' => $this->odds['away']];

        return array_keys($odds, min($odds))[0] ?? null;
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'match_id');
    }

    public function groupLetter(): ?string
    {
        $letters = preg_replace('/[^A-Za-z]/', '', (string) $this->group);

        return $letters !== '' ? strtoupper(substr($letters, -1)) : null;
    }

    public function isGroupStage(): bool
    {
        return $this->stage === self::GROUP_STAGE;
    }

    /**
     * The moment this match locks. Group matches all lock together at the
     * start of the group phase; knockout matches lock at their own kickoff.
     */
    public function locksAt(): ?CarbonInterface
    {
        if ($this->isGroupStage()) {
            return $this->tournament?->groupStageLocksAt();
        }

        return $this->kickoff_at;
    }

    public function isLocked(): bool
    {
        $locksAt = $this->locksAt();

        return $locksAt !== null && $locksAt->isPast();
    }

    public function isOpen(): bool
    {
        return ! $this->isLocked() && $this->homeTeam && $this->awayTeam;
    }

    public function hasResult(): bool
    {
        return $this->status?->isFinished()
            && $this->home_score !== null
            && $this->away_score !== null;
    }

    public function outcome(): ?string
    {
        if ($this->home_score === null || $this->away_score === null) {
            return null;
        }

        return match (true) {
            $this->home_score > $this->away_score => 'HOME_TEAM',
            $this->home_score < $this->away_score => 'AWAY_TEAM',
            default => 'DRAW',
        };
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereNotNull('kickoff_at')
            ->where('kickoff_at', '>=', now())
            ->orderBy('kickoff_at');
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->whereNotNull('kickoff_at')
            ->where('kickoff_at', '<', now());
    }
}
