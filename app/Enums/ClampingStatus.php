<?php

namespace App\Enums;

enum ClampingStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case WaitingRelease = 'waiting_release';
    case Released = 'released';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Awaiting Payment',
            self::Paid => 'Paid',
            self::WaitingRelease => 'Waiting to Release',
            self::Released => 'Released',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'tag-amber',
            self::Paid => 'tag-green',
            self::WaitingRelease => 'tag-amber',
            self::Released => 'tag-green',
        };
    }
}
