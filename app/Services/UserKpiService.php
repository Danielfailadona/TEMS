<?php

namespace App\Services;

use App\Enums\CitationStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Citation;
use App\Models\CitizenReport;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserKpiService
{
    /**
     * Compute the "My Contribution" KPI cards for a user, scoped by role.
     *
     * Cards are intentionally standalone tiles: each returns label, value,
     * a secondary caption, an icon and a Bootstrap colour token.
     *
     * @return array{scope: string, cards: list<array{label: string, value: string, caption: string, icon: string, color: string}>}
     */
    public function contribution(User $user): array
    {
        if ($user->isRole(Role::SuperAdmin, Role::Administrator)) {
            return $this->adminContribution();
        }

        if ($user->isCashier()) {
            return $this->cashierContribution($user);
        }

        if ($user->isRole(Role::Enforcer)) {
            return $this->enforcerContribution($user);
        }

        return ['scope' => 'Personal', 'cards' => []];
    }

    /**
     * System-wide totals for administrators.
     */
    private function adminContribution(): array
    {
        $citationsIssued = Citation::count();
        $reportsResolved = CitizenReport::where('status', ReportStatus::Resolved->value)->count();
        $paymentsRecorded = Payment::whereNotNull('paid_at')->count();
        $revenue = (float) Payment::whereNotNull('paid_at')->sum('amount');

        return [
            'scope' => 'System-wide',
            'cards' => [
                $this->card('Citations Issued', number_format($citationsIssued), 'All time', 'bi-receipt', 'primary'),
                $this->card('Reports Resolved', number_format($reportsResolved), 'All time', 'bi-check2-circle', 'success'),
                $this->card('Payments Recorded', number_format($paymentsRecorded), 'All time', 'bi-cash-coin', 'info'),
                $this->card('Total Revenue', '₱'.number_format($revenue, 2), 'Collected', 'bi-graph-up-arrow', 'success'),
            ],
        ];
    }

    /**
     * Cashier contribution: payments recorded, amount collected and receipts.
     */
    private function cashierContribution(User $user): array
    {
        $base = Payment::where('cashier_id', $user->id)->whereNotNull('paid_at');

        $count = (clone $base)->count();
        $amount = (float) (clone $base)->sum('amount');
        $today = (float) (clone $base)->whereDate('paid_at', today())->sum('amount');
        $todayCount = (clone $base)->whereDate('paid_at', today())->count();

        return [
            'scope' => 'My Contribution',
            'cards' => [
                $this->card('Payments Recorded', number_format($count), 'All time', 'bi-cash-coin', 'primary'),
                $this->card('Amount Collected', '₱'.number_format($amount, 2), 'All time', 'bi-graph-up-arrow', 'success'),
                $this->card('Receipts Issued', number_format($count), 'Printable via Archives', 'bi-receipt-cutoff', 'info'),
                $this->card('Collected Today', '₱'.number_format($today, 2), $todayCount.' payment(s)', 'bi-clock-history', 'warning'),
            ],
        ];
    }

    /**
     * Enforcer contribution: citations issued and citizen reports resolved.
     */
    private function enforcerContribution(User $user): array
    {
        $citationsIssued = Citation::where('issued_by', $user->id)->count();
        $reportsResolved = CitizenReport::where('assigned_to', $user->id)
            ->where('status', ReportStatus::Resolved->value)
            ->count();
        $reportsAssigned = CitizenReport::where('assigned_to', $user->id)->count();
        $clamps = DB::table('clamping_records')->where('clamped_by', $user->id)->count();

        return [
            'scope' => 'My Contribution',
            'cards' => [
                $this->card('Citations Issued', number_format($citationsIssued), 'All time', 'bi-receipt', 'primary'),
                $this->card('Reports Resolved', number_format($reportsResolved), 'of '.$reportsAssigned.' assigned', 'bi-check2-circle', 'success'),
                $this->card('Vehicles Clamped', number_format($clamps), 'All time', 'bi-lock', 'warning'),
                $this->card('Reports Assigned', number_format($reportsAssigned), 'All time', 'bi-inbox', 'info'),
            ],
        ];
    }

    private function card(string $label, string $value, string $caption, string $icon, string $color): array
    {
        return compact('label', 'value', 'caption', 'icon', 'color');
    }
}
