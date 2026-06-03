<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Tournament extends Model
{
    protected $guarded = [];

    /** Memoised deadline lookups so loops over matches don't re-query. */
    private array $deadlineCache = [];

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

    /**
     * The whole group stage closes at the kickoff of the very first group
     * match. After that, every group prediction locks and becomes visible.
     */
    public function groupStageLocksAt(): ?Carbon
    {
        return $this->deadlineCache['group'] ??= $this->earliestKickoff([GameMatch::GROUP_STAGE], exclude: false);
    }

    public function groupStageIsLocked(): bool
    {
        $locksAt = $this->groupStageLocksAt();

        return $locksAt !== null && $locksAt->isPast();
    }

    /**
     * Kickoff of the first knockout match. Bonus predictions stay editable
     * until this moment.
     */
    public function knockoutStartsAt(): ?Carbon
    {
        return $this->deadlineCache['knockout'] ??= $this->earliestKickoff([GameMatch::GROUP_STAGE], exclude: true);
    }

    /**
     * Bonus predictions (winner, finalist, top scorer) close at the start of
     * the knockout phase. While no knockout fixtures are scheduled yet they
     * stay open.
     */
    public function bonusesAreLocked(): bool
    {
        $locksAt = $this->knockoutStartsAt();

        return $locksAt !== null && $locksAt->isPast();
    }

    /**
     * Earliest kickoff among matches of (or excluding) the given stages.
     *
     * @param  array<int, string>  $stages
     */
    private function earliestKickoff(array $stages, bool $exclude): ?Carbon
    {
        $value = $this->matches()
            ->whereNotNull('kickoff_at')
            ->when(
                $exclude,
                fn ($q) => $q->whereNotNull('stage')->whereNotIn('stage', $stages),
                fn ($q) => $q->whereIn('stage', $stages),
            )
            ->min('kickoff_at');

        return $value ? Carbon::parse($value) : null;
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
