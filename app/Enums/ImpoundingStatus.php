<?php

namespace App\Enums;

enum ImpoundingStatus: string
{
    case Impounded = 'impounded';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case WaitingRelease = 'waiting_release';
    case Released = 'released';

    public function label(): string
    {
        return match ($this) {
            self::Impounded => 'Impounded',
            self::AwaitingPayment => 'Awaiting Payment',
            self::Paid => 'Paid',
            self::WaitingRelease => 'Waiting Release',
            self::Released => 'Released',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Impounded => 'tag-red',
            self::AwaitingPayment => 'tag-amber',
            self::Paid => 'tag-green',
            self::WaitingRelease => 'tag-gray',
            self::Released => 'tag-green',
        };
    }
}