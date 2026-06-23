<?php

namespace App\Enums;

enum AppealStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Submitted => 'bg-secondary',
            self::UnderReview => 'bg-info',
            self::Approved => 'bg-success',
            self::Rejected => 'bg-danger',
        };
    }
}
