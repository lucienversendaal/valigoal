<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Deelnemer = 'deelnemer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Beheerder',
            self::Deelnemer => 'Deelnemer',
        };
    }
}
