<?php

namespace App\Enums;

enum CitationStatus: string
{
    case Issued = 'issued';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Clamped = 'clamped';
    case Released = 'released';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'Issued',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Clamped => 'Clamped',
            self::Released => 'Released',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Issued => 'tag-blue',
            self::Paid => 'tag-green',
            self::Overdue => 'tag-amber',
            self::Clamped => 'tag-red',
            self::Released => 'tag-green',
        };
    }
}
