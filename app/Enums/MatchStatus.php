<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Scheduled = 'SCHEDULED';
    case Timed = 'TIMED';
    case InPlay = 'IN_PLAY';
    case Paused = 'PAUSED';
    case Finished = 'FINISHED';
    case Suspended = 'SUSPENDED';
    case Postponed = 'POSTPONED';
    case Cancelled = 'CANCELLED';

    public static function fromApi(?string $status): self
    {
        return self::tryFrom((string) $status) ?? self::Scheduled;
    }

    public function isFinished(): bool
    {
        return $this === self::Finished;
    }

    public function isLive(): bool
    {
        return in_array($this, [self::InPlay, self::Paused], true);
    }

    public function hasKickedOff(): bool
    {
        return in_array($this, [self::InPlay, self::Paused, self::Finished], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Scheduled, self::Timed => 'Gepland',
            self::InPlay => 'Live',
            self::Paused => 'Rust',
            self::Finished => 'Afgelopen',
            self::Suspended => 'Gestaakt',
            self::Postponed => 'Uitgesteld',
            self::Cancelled => 'Afgelast',
        };
    }
}
