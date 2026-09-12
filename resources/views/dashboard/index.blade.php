@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css">
<style>
    .trend-up { color:#16a34a; }
    .trend-down { color:#dc2626; }
    .trend-flat { color:#6b7280; }
    .pending-card {
        display:flex; align-items:center; gap:0.75rem;
        padding:0.75rem 1rem; border-radius:0.5rem;
        background:var(--itevcms-surface); border:1px solid var(--itevcms-border);
        font-size:0.85rem;
    }
    .pending-count {
        width:2rem; height:2rem; border-radius:0.4rem;
        display:flex; align-items:center; justify-content:center;
        font-weight:700; font-size:0.9rem; flex-shrink:0;
    }
    .chart-box { height:200px; }
    .chart-box-sm { height:150px; }
    .dash-map { width:100%; height:220px; border-radius:0.5rem; overflow:hidden; }

    .violation-item {
        display:flex; align-items:center; gap:20px; padding:12px 0;
    }
    .violation-label {
        width:190px; flex-shrink:0; font-size:16px; color:#1f2937;
    }
    .violation-bar-track {
        flex:1; height:14px; background:#e3e7f5; border-radius:7px; overflow:hidden;
    }
    .violation-bar-fill {
        height:100%; background:#2563eb; border-radius:7px;
        transition:width 0.4s ease;
    }
    .violation-count {
        width:24px; text-align:right; font-size:16px; font-weight:700; color:#1f2937; flex-shrink:0;
    }
</style>
@endpush

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
    $todaysTotal = $stats['revenue_today'];
@endphp

{{-- Header --}}
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-2 mb-4 animate-on-load">
    <div>
        <h1 class="h3 mb-1">{{ $greeting }}, {{ auth()->user()->name }}</h1>
<div class="d-flex flex-wrap gap-3 text-muted small">
            <span><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, F j, Y') }}</span>
        </div>
</div>
</div>

{{-- KPI Section (3-Tier) --}}
<div class="kpi-section">
    {{-- Tier 1: Needs Attention (Action Cards) --}}
    <div class="section-label">Needs attention</div>
    @php
        $unpaid = (int) $stats['unpaid_citations'];
        $stateUnpaid = $unpaid >= 29 ? 'state-red' : ($unpaid >= 17 ? 'state-amber' : 'state-green');
        $clamps = (int) $stats['active_clamps'];
        $stateClamps = $clamps >= 29 ? 'state-red' : ($clamps >= 17 ? 'state-amber' : 'state-green');
        $appeals = (int) $stats['pending_appeals'];
        $stateAppeals = $appeals >= 29 ? 'state-red' : ($appeals >= 17 ? 'state-amber' : 'state-green');
    @endphp
    <div class="tier1-row">
        {{-- Unpaid Citations --}}
        <div class="stat-card {{ $stateUnpaid }}">
            <div class="stat-label"><span class="state-dot"></span>Unpaid Citations</div>
            <div class="stat-value">{{ number_format($stats['unpaid_citations']) }}</div>
            @php
                $t = $trends['unpaid_citations'] ?? ['direction' => 'flat', 'percent' => 0];
            @endphp
            @if ($t['percent'] > 0 || $t['direction'] !== 'flat')
                <div class="stat-trend">
                    @if ($t['direction'] === 'up')
                        <i class="bi bi-arrow-up-short"></i>{{ $t['percent'] }}%
                    @elseif ($t['direction'] === 'down')
                        <i class="bi bi-arrow-down-short"></i>{{ $t['percent'] }}%
                    @else
                        <i class="bi bi-dash"></i>0%
                    @endif
                    <span class="text-muted">vs last week</span>
                </div>
            @endif
        </div>
        
        {{-- Active Clamps --}}
        <div class="stat-card {{ $stateClamps }}">
            <div class="stat-label"><span class="state-dot"></span>Active Clamps</div>
            <div class="stat-value">{{ number_format($stats['active_clamps']) }}</div>
            @php
                $t = $trends['active_clamps'] ?? ['direction' => 'flat', 'percent' => 0];
            @endphp
            @if ($t['percent'] > 0 || $t['direction'] !== 'flat')
                <div class="stat-trend">
                    @if ($t['direction'] === 'up')
                        <i class="bi bi-arrow-up-short"></i>{{ $t['percent'] }}%
                    @elseif ($t['direction'] === 'down')
                        <i class="bi bi-arrow-down-short"></i>{{ $t['percent'] }}%
                    @else
                        <i class="bi bi-dash"></i>0%
                    @endif
                    <span class="text-muted">vs last week</span>
                </div>
            @endif
        </div>
        
        {{-- Pending Appeals --}}
        <div class="stat-card {{ $stateAppeals }}">
            <div class="stat-label"><span class="state-dot"></span>Pending Appeals</div>
            <div class="stat-value">{{ number_format($stats['pending_appeals']) }}</div>
            @php
                $t = $trends['pending_appeals'] ?? ['direction' => 'flat', 'percent' => 0];
            @endphp
            @if ($t['percent'] > 0 || $t['direction'] !== 'flat')
                <div class="stat-trend">
                    @if ($t['direction'] === 'up')
                        <i class="bi bi-arrow-up-short"></i>{{ $t['percent'] }}%
                    @elseif ($t['direction'] === 'down')
                        <i class="bi bi-arrow-down-short"></i>{{ $t['percent'] }}%
                    @else
                        <i class="bi bi-dash"></i>0%
                    @endif
                    <span class="text-muted">vs last week</span>
                </div>
            @endif
        </div>
    </div>
    
    {{-- Tier 2: Pending Work (Promoted Work Queue) --}}
    <div class="section-label">Pending work</div>
    <div class="queue-section">
        <div class="queue-header">
            <h2>Pending Work Queue</h2>
            <span>Needs attention</span>
        </div>
        {{-- Pending Appeals --}}
        <div class="queue-row">
            <div class="queue-left">
                <div class="queue-count {{ $pendingQueue['appeals'] > 0 ? 'some' : 'zero' }}">
                    {{ $pendingQueue['appeals'] }}
                </div>
                <div>
                    <div class="queue-title">Pending Appeals</div>
                    <div class="queue-sub">Awaiting review decision</div>
                </div>
            </div>
            <a href="{{ route('appeals.index') }}" class="queue-btn">View</a>
        </div>
        
        {{-- Clamping Requests --}}
        <div class="queue-row">
            <div class="queue-left">
                <div class="queue-count {{ $pendingQueue['clamping_requests'] > 0 ? 'some' : 'zero' }}">
                    {{ $pendingQueue['clamping_requests'] }}
                </div>
                <div>
                    <div class="queue-title">Clamping Requests</div>
                    <div class="queue-sub">Citizen requests to review</div>
                </div>
            </div>
            <a href="{{ route('clamping-requests.index') }}" class="queue-btn">View</a>
        </div>
        
        {{-- Account Approvals (for non-enforcer/non-clamping officers) --}}
        @if (!auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
        <div class="queue-row">
            <div class="queue-left">
                <div class="queue-count {{ $pendingQueue['account_approvals'] > 0 ? 'some' : 'zero' }}">
                    {{ $pendingQueue['account_approvals'] }}
                </div>
                <div>
                    <div class="queue-title">Account Approvals</div>
                    <div class="queue-sub">New users awaiting approval</div>
                </div>
            </div>
            <a href="{{ route('users.index') }}" class="queue-btn">View</a>
        </div>
        @endif
    </div>
    
    {{-- Tier 3: Overview --}}
    <div class="section-label">Overview</div>
    <div class="two-col">
        <!-- Total Citations -->
        <div class="stat-card stat-card--quiet">
            <div class="stat-label">Total Citations</div>
            <div class="stat-value">{{ number_format($stats['total_citations']) }}</div>
        </div>
        
        <!-- Payments Today -->
        <div class="stat-card stat-card--quiet">
            <div class="stat-label">Payments Today</div>
            <div class="stat-value">₱{{ number_format($stats['revenue_today'], 2) }}</div>
        </div>
    </div>
</div>

{{-- Enforcer GPS Location --}}
@if (auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
<div class="row g-3 mb-4 animate-on-load">
    <div class="col-12 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-geo-alt me-2"></i>GPS Location</strong>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0"
                          style="width:44px;height:44px;background:var(--bs-primary-bg-subtle);">
                        <i class="bi bi-satellite text-primary"></i>
                    </span>
                    <div>
                        <div class="fw-semibold">Track your location</div>
                        <div class="text-muted small">Update your current GPS position for dispatch tracking</div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="gps-toggle">
                        <label class="form-check-label fw-semibold" for="gps-toggle">GPS Tracking</label>
                    </div>
                </div>
                <div id="gps-status" class="mb-2">
                    <span class="badge bg-secondary">Tracking paused</span>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2" id="gps-controls" style="display:none !important;">
                    <button type="button" id="gps-update-now" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-geo-alt-fill me-1"></i>Update Now
                    </button>
                    <select id="gps-interval" class="form-select form-select-sm" style="width:auto;">
                        <option value="5000" selected>Every 5s</option>
                        <option value="3000">Every 3s (fast)</option>
                        <option value="15000">Every 15s</option>
                    </select>
                </div>
                <div class="form-text">Requires GPS permission. Your location is sent to the tracking system for dispatch.</div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Analytics 2x2 Grid --}}
<div class="row g-4 mb-4 animate-on-load">
    {{-- Citations by Month --}}
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Citations by Month</strong></div>
            <div class="card-body p-0">
                @php
                    $cMonthKeys = $citationsByMonth->keys()->values();
                    $cMonthVals = $citationsByMonth->values();
                    $cMax = $cMonthVals->max() ?: 0;
                    $cN = $cMonthVals->count();
                @endphp
                @if ($cMax > 0)
                <svg class="mini-chart" viewBox="0 -12 300 132" xmlns="http://www.w3.org/2000/svg">
                    <line x1="30" y1="15" x2="270" y2="15" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="55" x2="270" y2="55" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="95" x2="270" y2="95" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <text x="24" y="19" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">{{ $cMax }}</text>
                    <text x="24" y="59" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">{{ round($cMax / 2) }}</text>
                    <text x="24" y="99" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">0</text>
@php
                    $cPoints = [];
                    // Start from origin (0 value at x=30, y=95)
                    $cPoints[] = '30,95';
                    foreach ($cMonthVals as $idx => $val) {
                        $px = ($cN > 1) ? 30 + ($idx * 240 / ($cN - 1)) : 30;
                        $py = 95 - ($val / $cMax) * 80;
                        $cPoints[] = round($px).','.round($py);
                    }
                    $cPolyline = implode(' ', $cPoints);
@endphp
                    <polyline points="{{ $cPolyline }}" fill="none" stroke="var(--itevcms-accent)" stroke-width="1.5"/>
                    @foreach ($cMonthVals as $idx => $val)
                        @php
                            $px = ($cN > 1) ? 30 + ($idx * 240 / ($cN - 1)) : 30;
                            $py = 95 - ($val / $cMax) * 80;
                        @endphp
                        <circle cx="{{ round($px) }}" cy="{{ round($py) }}" r="2.5" fill="var(--itevcms-accent)"/>
                        <text x="{{ round($px) }}" y="{{ round($py) - 7 }}" font-size="9" fill="var(--itevcms-accent)" text-anchor="middle" font-weight="700">{{ $val }}</text>
                        <text x="{{ round($px) }}" y="112" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="middle">{{ $cMonthKeys[$idx] }}</text>
                    @endforeach
                </svg>
                @else
                <svg class="mini-chart" viewBox="0 0 300 120" xmlns="http://www.w3.org/2000/svg">
                    <line x1="30" y1="15" x2="270" y2="15" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="55" x2="270" y2="55" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="95" x2="270" y2="95" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <text x="24" y="19" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">2</text>
                    <text x="24" y="59" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">1</text>
                    <text x="24" y="99" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">0</text>
                    <polyline points="30,95 270,95" fill="none" stroke="var(--itevcms-accent)" stroke-width="1.5" stroke-dasharray="4 4"/>
                    <circle cx="270" cy="95" r="2.5" fill="var(--itevcms-accent)"/>
                </svg>
                <div class="chart-caption">No citations recorded yet — this line will start moving once citations are issued.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Revenue Trend --}}
    @if (!auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Revenue Trend</strong></div>
            <div class="card-body p-0">
                @php
                    $rMonthKeys = $revenueByMonth->keys()->values();
                    $rMonthVals = $revenueByMonth->values();
                    $rMax = $rMonthVals->max() ?: 0;
                    $rN = $rMonthVals->count();
                @endphp
                @if ($rMax > 0)
                <svg class="mini-chart" viewBox="0 -12 300 132" xmlns="http://www.w3.org/2000/svg">
                    <line x1="30" y1="15" x2="270" y2="15" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="55" x2="270" y2="55" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="95" x2="270" y2="95" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <text x="24" y="19" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">₱{{ round($rMax) }}</text>
                    <text x="24" y="59" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">₱{{ round($rMax / 2) }}</text>
                    <text x="24" y="99" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">₱0</text>
@php
                    $rPoints = [];
                    // Start from origin (0 value at x=30, y=95)
                    $rPoints[] = '30,95';
                    foreach ($rMonthVals as $idx => $val) {
                        $px = ($rN > 1) ? 30 + ($idx * 240 / ($rN - 1)) : 30;
                        $py = 95 - ($val / $rMax) * 80;
                        $rPoints[] = round($px).','.round($py);
                    }
                    $rPolyline = implode(' ', $rPoints);
                    @endphp
                    <polyline points="{{ $rPolyline }}" fill="none" stroke="#16a34a" stroke-width="1.5"/>
                    @foreach ($rMonthVals as $idx => $val)
                        @php
                            $px = ($rN > 1) ? 30 + ($idx * 240 / ($rN - 1)) : 30;
                            $py = 95 - ($val / $rMax) * 80;
                            $rLabel = $val >= 1000 ? '₱'.round($val / 1000).'k' : '₱'.round($val);
                        @endphp
                        <circle cx="{{ round($px) }}" cy="{{ round($py) }}" r="2.5" fill="#16a34a"/>
                        <text x="{{ round($px) }}" y="{{ round($py) - 7 }}" font-size="9" fill="#16a34a" text-anchor="middle" font-weight="700">{{ $rLabel }}</text>
                        <text x="{{ round($px) }}" y="112" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="middle">{{ $rMonthKeys[$idx] }}</text>
                    @endforeach
                </svg>
                @else
                <svg class="mini-chart" viewBox="0 0 300 120" xmlns="http://www.w3.org/2000/svg">
                    <line x1="30" y1="15" x2="270" y2="15" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="55" x2="270" y2="55" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="95" x2="270" y2="95" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <text x="24" y="19" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">₱1</text>
                    <text x="24" y="59" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">₱0.5</text>
                    <text x="24" y="99" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">₱0</text>
                    <polyline points="30,95 270,95" fill="none" stroke="#16a34a" stroke-width="1.5" stroke-dasharray="4 4"/>
                    <circle cx="270" cy="95" r="2.5" fill="#16a34a"/>
                </svg>
                <div class="chart-caption">No payments recorded yet — this line will start moving once citations are paid.</div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Top Violation Types --}}
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Top Violation Types</strong></div>
            <div class="card-body">
                @php $maxCount = max($topViolations->max('count'), 1); @endphp
                @foreach ($topViolations as $v)
                    <div class="violation-item">
                        <span class="violation-label">{{ $v['name'] }}</span>
                        <div class="violation-bar-track">
                            @if ($v['count'] > 0)
                                <div class="violation-bar-fill" style="width:{{ ($v['count'] / $maxCount) * 100 }}%;"></div>
                            @endif
                        </div>
                        <span class="violation-count">{{ $v['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Appeals Trend --}}
    @if (!auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Appeals Trend</strong></div>
            <div class="card-body p-0">
                @php
                    $aMonthKeys = $appealsByMonth->keys()->values();
                    $aMonthVals = $appealsByMonth->values();
                    $aMax = $aMonthVals->max() ?: 0;
                    $aN = $aMonthVals->count();
                @endphp
                @if ($aMax > 0)
                <svg class="mini-chart" viewBox="0 -12 300 132" xmlns="http://www.w3.org/2000/svg">
                    <line x1="30" y1="15" x2="270" y2="15" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="55" x2="270" y2="55" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="95" x2="270" y2="95" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <text x="24" y="19" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">{{ $aMax }}</text>
                    <text x="24" y="59" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">{{ round($aMax / 2) }}</text>
                    <text x="24" y="99" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">0</text>
@php
                    $aPoints = [];
                    // Start from origin (0 value at x=30, y=95)
                    $aPoints[] = '30,95';
                    foreach ($aMonthVals as $idx => $val) {
                        $px = ($aN > 1) ? 30 + ($idx * 240 / ($aN - 1)) : 30;
                        $py = 95 - ($val / $aMax) * 80;
                        $aPoints[] = round($px).','.round($py);
                    }
                    $aPolyline = implode(' ', $aPoints);
                    @endphp
                    <polyline points="{{ $aPolyline }}" fill="none" stroke="#6d5bd0" stroke-width="3" stroke-linecap="round"/>
                    @foreach ($aMonthVals as $idx => $val)
                        @php
                            $px = ($aN > 1) ? 30 + ($idx * 240 / ($aN - 1)) : 30;
                            $py = 95 - ($val / $aMax) * 80;
                        @endphp
                        <circle cx="{{ round($px) }}" cy="{{ round($py) }}" r="4" fill="#6d5bd0"/>
                        <text x="{{ round($px) }}" y="{{ round($py) - 8 }}" font-size="9" fill="#6d5bd0" text-anchor="middle" font-weight="700">{{ $val }}</text>
                        <text x="{{ round($px) }}" y="112" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="middle">{{ $aMonthKeys[$idx] }}</text>
                    @endforeach
                </svg>
                @else
                <svg class="mini-chart" viewBox="0 0 300 120" xmlns="http://www.w3.org/2000/svg">
                    <line x1="30" y1="15" x2="270" y2="15" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="55" x2="270" y2="55" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <line x1="30" y1="95" x2="270" y2="95" stroke="var(--itevcms-border)" stroke-width="1"/>
                    <text x="24" y="19" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">2</text>
                    <text x="24" y="59" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">1</text>
                    <text x="24" y="99" font-size="9" fill="var(--itevcms-text-muted)" text-anchor="end">0</text>
                    <polyline points="30,95 270,95" fill="none" stroke="#6d5bd0" stroke-width="3" stroke-dasharray="2 14" stroke-linecap="round"/>
                    <circle cx="270" cy="95" r="4" fill="#6d5bd0"/>
                </svg>
                <div class="chart-caption">No appeals filed yet — this will chart appeal volume once filed.</div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
{{-- Recent Activity (full width) — admin only --}}
@if (!auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
<div class="card stat-card mb-4 animate-on-load">
    <div class="card-header bg-white">
        <strong>Recent Activity</strong>
        <p class="text-muted small mb-0">Latest enforcement events across the office</p>
    </div>
    <div class="card-body">
        @forelse ($recentActivity as $item)
            <div class="d-flex align-items-start justify-content-between gap-3 border-bottom py-3">
                <div class="d-flex gap-3 min-width-0 flex-grow-1">
                    <div class="activity-icon flex-shrink-0">
                        @if ($item['type'] === 'payment')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><line x1="6" y1="10" x2="6.01" y2="10"/><line x1="18" y1="14" x2="18.01" y2="14"/></svg>
                        @elseif ($item['type'] === 'appeal')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 8.5-8.5 8.38 8.38 0 0 1 8.5 8.5z"/></svg>
                        @elseif ($item['type'] === 'clamp')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        @else
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="8.5" y1="8" x2="15.5" y2="8"/><line x1="8.5" y1="12" x2="15.5" y2="12"/><line x1="8.5" y1="16" x2="13" y2="16"/></svg>
                        @endif
                    </div>
                    <div class="min-width-0">
                        <div class="fw-semibold small">{{ $item['title'] }}</div>
                        <div class="text-muted small">{{ $item['description'] }}</div>
                        @if (!empty($item['meta']))
                            <div class="text-muted" style="font-size:0.7rem;">{{ $item['meta'] }}</div>
                        @endif
                    </div>
                </div>
                <div class="text-muted small text-nowrap flex-shrink-0">{{ $item['timestamp_label'] }}</div>
            </div>
        @empty
            <div class="text-muted text-center py-4">No recent activity yet.</div>
        @endforelse
    </div>
</div>
@endif

{{-- Quick Actions + Zone Coverage --}}
<div class="row g-4 animate-on-load">
    <div class="col-xl-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white">
                <strong>Quick Actions</strong>
            </div>
            <div class="card-body d-grid gap-2">
                @can('create', App\Models\Citation::class)
                    <a href="{{ route('citations.create') }}" class="btn btn-outline-primary text-start"><i class="bi bi-receipt me-2"></i>Issue Citation</a>
                @endcan
                @can('create', App\Models\Payment::class)
                    <a href="{{ route('payments.create') }}" class="btn btn-outline-success text-start"><i class="bi bi-cash-stack me-2"></i>Process Payment</a>
                @endcan
                @can('create', App\Models\ClampingRecord::class)
                    <a href="{{ route('clamping.create') }}" class="btn btn-outline-danger text-start"><i class="bi bi-lock me-2"></i>Record Clamp</a>
                @endcan
                @can('viewAny', App\Models\Appeal::class)
                    <a href="{{ route('appeals.index') }}" class="btn btn-outline-secondary text-start"><i class="bi bi-chat-square-text me-2"></i>Review Appeals</a>
                @endcan
            </div>
        </div>
    </div>

    @if (!auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
    <div class="col-xl-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Zone Coverage</strong>
                <a href="{{ route('zones.index') }}" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem;">Manage</a>
            </div>
            <div class="card-body p-0">
                @if ($zoneMapData->isEmpty())
                    <div class="text-center text-muted py-5 small">No active zones configured.</div>
                @else
                    <div id="dashboard-zone-map" class="dash-map"></div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
@vite('resources/js/zone-picker.js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const citationLabels = @json($citationsByMonth->keys()->values());
    const citationData = @json($citationsByMonth->values());
    const revenueLabels = @json($revenueByMonth->keys()->values());
    const revenueData = @json($revenueByMonth->values());
    const appealLabels = @json($appealsByMonth->keys()->values());
    const appealData = @json($appealsByMonth->values());

    function initCitationChart() {
        const el = document.getElementById('citationsChart');
        if (!el || typeof Chart === 'undefined') return;
        try {
            new Chart(el, {
                type: 'bar',
                data: {
                    labels: citationLabels,
                    datasets: [{ label: 'Citations', data: citationData, backgroundColor: '#2563eb', borderRadius: 4 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        } catch (e) { console.warn('Citation chart failed:', e); }
    }

    function initRevenueChart() {
        const el = document.getElementById('revenueChart');
        if (!el || typeof Chart === 'undefined') return;
        try {
            new Chart(el, {
                type: 'line',
                data: {
                    labels: revenueLabels,
                    datasets: [{ label: 'Revenue (₱)', data: revenueData, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.08)', fill: true, tension: 0.35 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { callback: v => '₱' + Number(v).toLocaleString() } } }
                }
            });
        } catch (e) { console.warn('Revenue chart failed:', e); }
    }

    function initAppealsChart() {
        const el = document.getElementById('appealsChart');
        if (!el || typeof Chart === 'undefined') return;
        try {
            new Chart(el, {
                type: 'line',
                data: {
                    labels: appealLabels,
                    datasets: [{ label: 'Appeals', data: appealData, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,0.08)', fill: true, tension: 0.35 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        } catch (e) { console.warn('Appeals chart failed:', e); }
    }

    initCitationChart();
    initRevenueChart();
    initAppealsChart();

    if (window.__zonePicker?.initZoneViewer) {
        const zoneData = @json($zoneMapData);
        if (zoneData.length) {
            window.__zonePicker.initZoneViewer('dashboard-zone-map', {
                zones: zoneData, zoom: 10,
            });
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Enforcer GPS toggle-based tracking (isolated so chart failures can't block it)
    const ACCURACY_GOOD = 50;
    const ACCURACY_FAIR = 150;
    const ACCURACY_TARGET = 75;
    const MAX_ACQUIRE_ATTEMPTS = 8;
    const MAX_ACQUIRE_MS = 20000;

    const gpsToggle = document.getElementById('gps-toggle');
    const gpsStatusEl = document.getElementById('gps-status');
    const gpsControls = document.getElementById('gps-controls');
    const gpsUpdateNow = document.getElementById('gps-update-now');
    const gpsIntervalSelect = document.getElementById('gps-interval');
    let gpsPollTimer = null;
    let gpsWatchId = null;

    if (gpsToggle && gpsStatusEl) {
        function accuracyClass(m) {
            if (m <= ACCURACY_GOOD) return 'bg-success';
            if (m <= ACCURACY_FAIR) return 'bg-warning text-dark';
            return 'bg-danger';
        }

        function acquiringBadge(m) {
            return '<span class="badge ' + accuracyClass(m) + '">Acquiring… ±' + Math.round(m) + 'm</span>';
        }

        function stopAcquiringWatch() {
            if (gpsWatchId !== null) {
                navigator.geolocation.clearWatch(gpsWatchId);
                gpsWatchId = null;
            }
        }

        async function sendFixToServer(lat, lng, accuracy) {
            gpsStatusEl.innerHTML = '<span class="badge bg-info"><span class="spinner-border spinner-border-sm me-1"></span>Sending…</span>';
            try {
                const res = await fetch('{{ route("location.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ latitude: lat, longitude: lng, accuracy_m: Math.round(accuracy) })
                });
                if (res.ok) {
                    const sec = gpsToggle.checked ? Math.round(parseInt(gpsIntervalSelect.value) / 1000) : null;
                    gpsStatusEl.innerHTML = sec
                        ? '<span class="badge bg-success">Active · ±' + Math.round(accuracy) + 'm · every ' + sec + 's</span>'
                        : '<span class="badge bg-success">Location updated ✓ · ±' + Math.round(accuracy) + 'm</span>';
                } else {
                    let msg = 'Server error (' + res.status + ')';
                    try {
                        const errBody = await res.text();
                        try { const j = JSON.parse(errBody); msg = j.message || j.error || msg; } catch (_) { if (errBody) msg = errBody.substring(0, 80); }
                    } catch (_) {}
                    gpsStatusEl.innerHTML = '<span class="badge bg-danger">Failed: ' + msg + '</span>';
                }
            } catch (e) {
                gpsStatusEl.innerHTML = '<span class="badge bg-danger">Network error</span>';
            }
        }

        function acquireAndSend() {
            if (!navigator.geolocation) {
                gpsStatusEl.innerHTML = '<span class="badge bg-danger">Geolocation not supported</span>';
                return;
            }

            let best = null;
            let attempts = 0;
            const startedAt = Date.now();
            gpsStatusEl.innerHTML = '<span class="badge bg-info"><span class="spinner-border spinner-border-sm me-1"></span>Acquiring GPS…</span>';

            stopAcquiringWatch();

            gpsWatchId = navigator.geolocation.watchPosition(
                (pos) => {
                    const { latitude, longitude, accuracy } = pos.coords;
                    attempts++;
                    if (!best || accuracy < best.accuracy) best = { latitude, longitude, accuracy };
                    gpsStatusEl.innerHTML = acquiringBadge(best.accuracy);

                    const converged = best.accuracy <= ACCURACY_TARGET;
                    const capped = attempts >= MAX_ACQUIRE_ATTEMPTS || (Date.now() - startedAt) >= MAX_ACQUIRE_MS;
                    if (converged || capped) {
                        stopAcquiringWatch();
                        sendFixToServer(best.latitude, best.longitude, best.accuracy);
                    }
                },
                (err) => {
                    stopAcquiringWatch();
                    const gpsErrors = { 1: 'GPS permission denied', 2: 'GPS position unavailable', 3: 'GPS request timed out' };
                    gpsStatusEl.innerHTML = '<span class="badge bg-danger">' + (gpsErrors[err.code] || 'GPS error') + '</span>';
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        }

        function startGPSPolling() {
            const ms = parseInt(gpsIntervalSelect.value) || 5000;
            acquireAndSend();
            if (gpsPollTimer) clearInterval(gpsPollTimer);
            gpsPollTimer = setInterval(acquireAndSend, ms);
        }

        function stopGPSPolling() {
            if (gpsPollTimer) { clearInterval(gpsPollTimer); gpsPollTimer = null; }
            stopAcquiringWatch();
            gpsStatusEl.innerHTML = '<span class="badge bg-secondary">Tracking paused</span>';
        }

        gpsToggle.addEventListener('change', () => {
            if (gpsToggle.checked) {
                gpsControls.style.display = 'flex';
                startGPSPolling();
            } else {
                gpsControls.style.display = 'none';
                stopGPSPolling();
            }
        });

        gpsUpdateNow?.addEventListener('click', () => acquireAndSend());

        gpsIntervalSelect?.addEventListener('change', () => {
            if (gpsToggle.checked) startGPSPolling();
        });

        window.addEventListener('pagehide', stopGPSPolling);
        window.addEventListener('beforeunload', stopGPSPolling);
    }
});
</script>
@endpush
