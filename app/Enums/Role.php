<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Administrator = 'administrator';
    case Enforcer = 'enforcer';
    case ClampingOfficer = 'clamping_officer';
    case Cashier = 'cashier';
    case FrontDesk = 'front_desk';
    case VehicleOwner = 'vehicle_owner';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrator',
            self::Administrator => 'Administrator',
            self::Enforcer => 'Transportation Enforcer',
            self::ClampingOfficer => 'Clamping Officer',
            self::Cashier => 'Cashier',
            self::FrontDesk => 'Front Desk',
            self::VehicleOwner => 'Vehicle Owner',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::VehicleOwner;
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Administrator], true);
    }

    /**
     * @return list<self>
     */
    public static function staffRoles(): array
    {
        return array_filter(self::cases(), fn (self $role) => $role->isStaff());
    }
}
