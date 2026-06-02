<?php

namespace App\Enums;

enum BonusType: string
{
    case Winner = 'winner';
    case Finalist = 'finalist';
    case TopScorer = 'top_scorer';

    public function points(): int
    {
        return match ($this) {
            self::Winner => 15,
            self::Finalist => 10,
            self::TopScorer => 10,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Winner => 'Toernooiwinnaar',
            self::Finalist => 'Finalist',
            self::TopScorer => 'Topscorer',
        };
    }

    public function isPlayer(): bool
    {
        return $this === self::TopScorer;
    }
}
