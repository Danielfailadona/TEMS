<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Resolved = 'resolved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Under Review',
            self::Accepted => 'Accepted',
            self::Resolved => 'Resolved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#f59e0b',
            self::Accepted, self::Resolved => '#10B981',
            self::Rejected => '#EF4444',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'tag-amber',
            self::Accepted, self::Resolved => 'tag-green',
            self::Rejected => 'tag-red',
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
