<?php

namespace App\Services\Scoring;

class ScoreResult
{
    public function __construct(
        public int $points,
        public bool $isExact,
        public bool $isCorrectOutcome,
        public bool $isCorrectGoalDifference,
    ) {}
}
