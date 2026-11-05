<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#f59e0b',
            self::Approved => '#10B981',
            self::Rejected => '#EF4444',
            self::Suspended => '#69757F',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'tag-amber',
            self::Approved => 'tag-green',
            self::Rejected => 'tag-red',
            self::Suspended => 'tag-gray',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
