@extends('layouts.app')

@section('title', 'Cashier Dashboard')

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
@endphp

{{-- Header --}}
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-2 mb-4 animate-on-load">
    <div>
        <h1 class="h3 mb-1">{{ $greeting }}, {{ auth()->user()->name }}</h1>
        <div class="d-flex flex-wrap gap-3 text-muted small">
            <span><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, F j, Y') }}</span>
            <span><i class="bi bi-person-badge me-1"></i>{{ auth()->user()->role->label() }}</span>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('payments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Record Payment</a>
        <a href="{{ route('archives.index') }}" class="btn btn-outline-secondary"><i class="bi bi-archive"></i> Receipts &amp; Archives</a>
    </div>
</div>

{{-- Per-user contribution KPIs --}}
@include('dashboard.partials.my-contribution')

{{-- Today at a glance --}}
<div class="section-label">Today at a glance</div>
<div class="row g-3 mb-4 animate-on-load">
    @foreach ([
        ['label' => 'Collected Today', 'value' => '₱'.number_format($stats['collected_today'], 2), 'icon' => 'bi-cash-coin', 'color' => 'success'],
        ['label' => 'Payments Today', 'value' => number_format($stats['payments_today']), 'icon' => 'bi-receipt-cutoff', 'color' => 'primary'],
        ['label' => 'Unpaid Citations', 'value' => number_format($stats['unpaid_citations']), 'icon' => 'bi-exclamation-circle', 'color' => 'warning'],
        ['label' => 'Overdue Citations', 'value' => number_format($stats['overdue_citations']), 'icon' => 'bi-clock-history', 'color' => 'danger'],
        ['label' => 'Active Clamps', 'value' => number_format($stats['active_clamps']), 'icon' => 'bi-lock', 'color' => 'secondary'],
    ] as $tile)
        <div class="col-sm-6 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $tile['color'] }}-subtle text-{{ $tile['color'] }}" style="width:2.75rem;height:2.75rem;">
                        <i class="bi {{ $tile['icon'] }} fs-5"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="text-muted small">{{ $tile['label'] }}</div>
                        <div class="h4 mb-0">{{ $tile['value'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Quick actions --}}
<div class="section-label">Quick actions</div>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="{{ route('payments.create') }}" class="text-decoration-none">
            <div class="card stat-card h-100 border-0">
                <div class="card-body text-center py-4">
                    <i class="bi bi-cash-stack fs-1 text-success mb-2 d-block"></i>
                    <h6 class="mb-0">Record Payment</h6>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('payments.index') }}" class="text-decoration-none">
            <div class="card stat-card h-100 border-0">
                <div class="card-body text-center py-4">
                    <i class="bi bi-receipt fs-1 text-primary mb-2 d-block"></i>
                    <h6 class="mb-0">Find Receipt</h6>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('reports.overdue-citations') }}" class="text-decoration-none">
            <div class="card stat-card h-100 border-0">
                <div class="card-body text-center py-4">
                    <i class="bi bi-clock-history fs-1 text-danger mb-2 d-block"></i>
                    <h6 class="mb-0">Overdue Citations</h6>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Recent payments recorded by me --}}
<div class="card stat-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>My Recent Payments</strong>
        <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Category</th>
                    <th>Notice</th>
                    <th class="text-end">Amount</th>
                    <th>Paid</th>
                    <th class="text-end">Receipt</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentPayments as $payment)
                    <tr>
                        <td><a href="{{ route('payments.show', $payment) }}">{{ $payment->receipt_number }}</a></td>
                        <td><span class="badge bg-light text-dark">{{ $payment->categoryLabel() }}</span></td>
                        <td>{{ $payment->payableNoticeNumber() }}</td>
                        <td class="text-end fw-semibold">₱{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ ($payment->paid_at ?? $payment->created_at)?->format('M j, Y g:i A') }}</td>
                        <td class="text-end">
                            <a href="{{ route('payments.print', $payment) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="bi bi-printer"></i> Print
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">You have not recorded any payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
