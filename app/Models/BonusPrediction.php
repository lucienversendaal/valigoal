<?php

namespace App\Models;

use App\Enums\BonusType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusPrediction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => BonusType::class,
            'points' => 'integer',
            'is_correct' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
