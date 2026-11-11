@extends('layouts.app')

@section('title', 'Users')

@push('styles')
<style>
    :root {
        --cr-navy: #0d2b49;
        --cr-blue: #176ff2;
        --cr-ink: #182235;
        --cr-muted: #78869a;
        --cr-line: #dfe5eb;
        --cr-blue: #176ff2;
        --cr-blue-soft: #eaf3ff;
    }
    .cr-dash .cr-toolbar { display: flex; align-items: center; gap: 16px; width: 100%; flex-wrap: wrap; }
    .cr-dash .cr-filter-wrap { position: relative; flex: 0 0 128px; }
    .cr-dash .cr-filter-wrap .form-select {
        width: 100%;
        height: 38px;
        border: 1px solid #d7dee7;
        border-radius: 4px;
        font-size: 14px;
        color: #111827;
        background: #fff;
    }
    .cr-dash .cr-filter-wrap .form-select:focus {
        border-color: #176ff2;
        box-shadow: 0 0 0 0.2rem rgba(23, 111, 242, 0.25);
    }
    .cr-dash .cr-filter-button { 
        height: 38px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; 
        background: #fff; border: 1px solid #d7dee7; color: #111827; font-weight: 650; font-size: 16px; 
        border-radius: 4px; cursor: pointer; 
    }
    .cr-dash .cr-filter-button svg { width: 15px; height: 15px; }
    .cr-dash .cr-filter-button[aria-expanded="true"] svg { transform: rotate(180deg); }
    .cr-dash .cr-caret { transition: transform .18s ease; }

    .cr-dash .cr-filter-panel {
        display: none; position: absolute; top: calc(100% + 8px); left: 0; z-index: 30;
        width: 421px; max-width: min(421px, 88vw); padding: 22px 18px 28px;
        background: #fff; border: 1px solid #dfe5eb; border-radius: 8px;
        box-shadow: 0 12px 32px rgba(13, 43, 72, .16), 0 2px 6px rgba(13, 43, 72, .08);
    }
    .cr-dash .cr-filter-panel.open { display: grid; gap: 22px; }
    .cr-dash .cr-field { border: 0; padding: 0; margin: 0; min-width: 0; }
    .cr-dash .cr-field__label { font-size: 16px; font-weight: 700; color: #000; padding: 0; margin-bottom: 10px; display: block; }

    .cr-dash .cr-select { position: relative; }
    .cr-dash .cr-select select {
        appearance: none; -webkit-appearance: none; width: 100%; height: 46px; padding: 0 44px 0 16px;
        background: #fff; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: var(--cr-ink);
    }
    .cr-dash .cr-select select:focus { border-color: #3b82f6; outline: 2px solid rgba(59,130,246,.25); outline-offset: 0; }
    .cr-dash .cr-select svg {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        width: 16px; height: 16px; color: var(--cr-ink); pointer-events: none;
    }

    .cr-dash .cr-panel-actions { display: flex; gap: 14px; margin-top: 4px; }
    .cr-dash .cr-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        height: 40px; padding: 0 14px; border-radius: 4px; border: 1px solid transparent;
        font-size: 11px; font-weight: 700; text-decoration: none; white-space: nowrap; cursor: pointer;
    }
    .cr-dash .cr-btn svg { width: 14px; height: 14px; }
    .cr-dash .cr-btn--primary { background: var(--cr-blue); color: #fff; }
    .cr-dash .cr-btn--primary:hover { background: #075edb; color: #fff; }
    .cr-dash .cr-btn--neutral { background: #6b7280; color: #fff; }
    .cr-dash .cr-btn--neutral:hover { background: #565d6a; color: #fff; }

    .cr-dash .cr-search-box { display: flex; height: 38px; min-width: 100px; flex: 1; }
    .cr-dash .cr-search-submit {
        display: grid; place-items: center; width: 34px; flex: 0 0 34px; background: #fff;
        color: var(--cr-blue); border: 1px solid var(--cr-blue); border-radius: 4px 0 0 4px; cursor: pointer;
    }
    .cr-dash .cr-search-submit svg { width: 19px; height: 19px; stroke: currentColor; }
    .cr-dash .cr-search-box input {
        min-width: 0; width: 100%; border: 1px solid #d7dee7; border-left: 0; padding: 0 22px;
        outline: none; background: #fff; color: #263247; font-size: 16px; border-radius: 0 4px 4px 0;
    }
    .cr-dash .cr-search-box input:focus { border-color: #7aa9f8; box-shadow: inset 0 0 0 1px #7aa9f8; }

    .cr-dash .cr-primary-btn {
        height: 38px; border: 0; background: var(--cr-blue); color: #fff; border-radius: 16px;
        padding: 0 16px; font-weight: 650; white-space: nowrap; display: inline-flex; align-items: center;
        gap: 6px; text-decoration: none; font-size: 14px;
    }
    .cr-dash .cr-primary-btn:hover { background: #075edb; color: #fff; }

    .cr-dash .cr-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px; }
    .cr-dash .cr-chip {
        display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 6px 0 10px;
        background: #eef5ff; border: 1px solid #cfe0fb; border-radius: 13px;
        font-size: 12px; color: #1473F9; text-decoration: none;
    }
    .cr-dash .cr-chip:hover { background: #e2eeff; color: #145fc8; }
    .cr-dash .cr-chip__x { display: grid; place-items: center; width: 18px; height: 18px; border-radius: 50%; color: inherit; }
    .cr-dash .cr-chip__x svg { width: 11px; height: 11px; stroke: currentColor; stroke-width: 2.2; }

    .cr-dash .cr-table-wrap { margin-top: 24px; overflow: visible; background: #fff; border: 1px solid var(--cr-line); border-radius: 12px; }
    .cr-dash .cr-table { width: 100%; border-collapse: collapse; table-layout: fixed; background: #fff; }

    /* KPI Cards */
    .cr-dash .cr-kpi-card {
        background: #fff;
        border: 1px solid var(--cr-line);
        border-radius: 8px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .cr-dash .cr-kpi-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--cr-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .cr-dash .cr-kpi-value {
        font-size: 24px;
        font-weight: 700;
        color: var(--cr-ink);
        line-height: 1;
    }
    .cr-kpi-value.pending-green { color: #10b981; }
    .cr-kpi-value.pending-amber { color: #f59e0b; }
    .cr-kpi-value.pending-red { color: #ef4444; }

    /* Filter panel - force hidden by default */
    .cr-dash .cr-filter-panel {
        display: none !important;
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        z-index: 30;
        width: 421px;
        max-width: min(421px, 88vw);
        padding: 22px 18px 28px;
        background: #fff;
        border: 1px solid var(--cr-line);
        border-radius: 8px;
        box-shadow: 0 12px 32px rgba(13, 43, 72, .16), 0 2px 6px rgba(13, 43, 72, .08);
    }
    .cr-dash .cr-filter-panel.open {
        display: grid !important;
        gap: 22px;
    }
    .cr-dash .cr-table thead th {
        height: 51px; text-align: left; font-size: 14px; font-weight: 600; padding: 0 16px;
        border-bottom: 2px solid #e8edf2; white-space: nowrap; color: var(--cr-ink);
    }
    .cr-dash .cr-table tbody tr { height: 78px; border-bottom: 2px solid #e8edf2; }
    .cr-dash .cr-table tbody tr:last-child { border-bottom: 0; }
    .cr-dash .cr-table tbody td, .cr-dash .cr-table tbody th { padding: 0 16px; white-space: nowrap; font-size: 14px; color: var(--cr-ink); overflow: visible; text-overflow: ellipsis; }
    .cr-dash .cr-table tbody th { font-weight: 600; text-align: left; }
    .cr-dash .cr-table th:nth-child(1), .cr-dash .cr-table td:nth-child(1) { width: 6% !important; }
    .cr-dash .cr-table th:nth-child(2), .cr-dash .cr-table td:nth-child(2) { width: 20% !important; }
    .cr-dash .cr-table th:nth-child(3), .cr-dash .cr-table td:nth-child(3) { width: 20% !important; }
    .cr-dash .cr-table th:nth-child(4), .cr-dash .cr-table td:nth-child(4) { width: 14% !important; }
    .cr-dash .cr-table th:nth-child(5), .cr-dash .cr-table td:nth-child(5) { width: 15% !important; }
    .cr-dash .cr-table th:nth-child(6), .cr-dash .cr-table td:nth-child(6) { width: 13% !important; overflow: visible; position: relative; }
    @media (max-width: 767.98px) {
        .cr-dash .cr-table { table-layout: auto; }
        .cr-dash .cr-table th:nth-child(n), .cr-dash .cr-table td:nth-child(n) { width: auto !important; }
    }

    .cr-dash .cr-pill {
        display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
        border-radius: 7px; min-width: 40px; text-align: center; white-space: nowrap;
    }
    .cr-dash .cr-actions { display: inline-flex; align-items: center; gap: 2px; position: relative; }
    .cr-dash .cr-actions-menu-btn {
        background: #f8f9fc;
        border: 1px solid #d1d5db;
        border-radius: 20px;
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    .cr-dash .cr-actions-menu-btn svg {
        width: 16px;
        height: 16px;
        stroke-width: 2;
    }
    .cr-dash .cr-actions-menu {
        position: absolute;
        top: calc(100% + 4px);
        right: 20%;
        transform: translateX(0);
        z-index: 50;
        min-width: 140px;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 4px 0;
        display: none;
        list-style: none;
        margin: 0;
    }
    .cr-dash .cr-actions-menu.open {
        display: block;
    }
    .cr-dash .cr-actions-menu-item {
        display: block;
        padding: 8px 16px;
        color: #374151;
        text-decoration: none;
        font-size: 14px;
    }
    .cr-dash .cr-actions-menu-item:hover {
        background: #eff6ff;
    }
    .cr-dash .cr-actions-menu-item svg {
        width: 14px;
        height: 14px;
        margin-right: 8px;
        stroke-width: 2;
    }
    .cr-dash .cr-actions-menu-item.suspend-action {
        color: #d97706;
    }
    .cr-dash .cr-actions-menu-item.suspend-action svg {
        stroke: #d97706;
    }
    .cr-dash .cr-pill-btn {
        height: 30px; min-width: 40px; border-radius: 15px; display: inline-grid; place-items: center;
        text-decoration: none; border: 1px solid var(--pym-blue); color: var(--pym-blue); background: #fff;
    }
    .cr-dash .cr-pill-btn svg { width: 16px; height: 16px; stroke: currentColor; }
    .cr-dash .cr-pill-btn:hover { background: #edf5ff; }
    .cr-dash .cr-pill-btn--edit { border-color: #d0d7de; color: #4b5563; }
    .cr-dash .cr-pill-btn--edit:hover { background: #f8fafc; }

    .cr-dash .cr-collect-btn {
        height: 30px; padding: 0 14px; border-radius: 15px; border: 1px solid var(--pym-blue);
        background: var(--pym-blue); color: #fff; font-size: 11px; font-weight: 700;
        display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
    }
    .cr-dash .cr-collect-btn:hover { background: #075edb; color: #fff; }
    .cr-dash .cr-collect-btn svg { width: 14px; height: 14px; }

    .cr-dash .cr-empty { padding: 45px 20px; text-align: center; color: #738197; }
    .cr-dash .cr-table-footer {
        min-height: 42px; display: flex; justify-content: space-between; align-items: center; gap: 16px;
        color: #74839a; font-size: 11px; padding: 12px 4px 0; flex-wrap: wrap;
    }
    .cr-dash .pagination { display: flex; align-items: center; gap: 0; margin-bottom: 0; }
    .cr-dash .pagination .page-link {
        width: 38px; height: 30px; background: #fff; border: 1px solid #dce3eb; color: #176ff2;
        margin-left: -1px; display: grid; place-items: center; font-size: 13px; border-radius: 0;
        padding: 0; text-decoration: none;
    }
    .cr-dash .pagination .page-item:first-child .page-link { border-radius: 5px 0 0 5px; color: #60728a; }
    .cr-dash .pagination .page-item:last-child .page-link { border-radius: 0 5px 5px 0; color: #60728a; }
    .cr-dash .pagination .page-item.active .page-link { background: #176ff2; border-color: #176ff2; color: #fff; }
    .cr-dash .pagination .page-item.disabled .page-link { background: #fff; color: #c3cdd9; }

    .cr-kpi-value.pending-green { color: #10b981; }
    .cr-kpi-value.pending-amber { color: #f59e0b; }
    .cr-kpi-value.pending-red { color: #ef4444; }
</style>
@endpush

@section('content')
<div class="cr-dash">
    {{-- KPI Cards Row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="cr-kpi-card">
                <div class="cr-kpi-label">Total Users</div>
                <div class="cr-kpi-value">{{ number_format($totalUsers) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cr-kpi-card">
                <div class="cr-kpi-label">Pending Approval</div>
                <div class="cr-kpi-value pending-{{ $pendingState }}">{{ number_format($pending) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cr-kpi-card">
                <div class="cr-kpi-label">Active Today</div>
                <div class="cr-kpi-value">{{ number_format($activeToday) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cr-kpi-card">
                <div class="cr-kpi-label">Suspended</div>
                <div class="cr-kpi-value">{{ number_format($suspendedUsers) }}</div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="cr-toolbar mb-4">
        {{-- Filters Dropdown (Payments-style) --}}
        <div class="cr-filter-wrap">
            <button type="button" class="cr-filter-button" id="crFilterBtn" aria-expanded="false" aria-haspopup="dialog" aria-controls="crFilterPanel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                <span>Filters</span>
            </button>
            <div class="cr-filter-panel" id="crFilterPanel" role="dialog" aria-label="Filter users">
                <fieldset class="cr-field">
                    <legend class="cr-field__label">Role</legend>
                    <div class="cr-select">
                        <select name="role" id="crRoleSelect">
                            <option value="">All Roles</option>
                            @foreach (\App\Enums\Role::cases() as $role)
                                <option value="{{ $role->value }}" {{ request('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                            @endforeach
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </fieldset>
                <fieldset class="cr-field">
                    <legend class="cr-field__label">Status</legend>
                    <div class="cr-select">
                        <select name="account_status" id="crStatusSelect">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('account_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('account_status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('account_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="suspended" {{ request('account_status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </fieldset>
                <div class="cr-panel-actions">
                    <button type="button" class="cr-btn cr-btn--primary" id="crApplyBtn">Apply</button>
                    <a href="{{ $urlClearFilters }}" class="cr-btn cr-btn--neutral">Clear</a>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <form action="{{ route('users.index') }}" method="GET" class="cr-search-box" role="search">
            <button type="submit" class="cr-search-submit" aria-label="Search users">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </button>
            <input type="search" name="search" placeholder="Search users..." value="{{ request('search') }}" aria-label="Search users">
            @foreach (array_diff_key(request()->except('search'), ['search' => '']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
        </form>

        {{-- Add User Button --}}
        <a href="{{ route('users.create') }}" class="cr-primary-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add User
        </a>
    </div>

    {{-- Active Filter Chips --}}
    @if (!empty($activeFilters))
    <div class="cr-chips" id="crActiveFilters">
        @foreach ($activeFilters as $filter)
            <a href="{{ $filter['href'] }}" class="cr-chip">
                {{ $filter['label'] }}
                <span class="cr-chip__x" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </span>
            </a>
        @endforeach
    </div>
    @endif

    {{-- Batch Actions Toolbar --}}
    <form action="{{ route('users.batch') }}" method="POST" id="usersBatchForm" class="d-none">
        @csrf
        <input type="hidden" name="action" id="batchAction">
        <input type="hidden" name="user_ids" id="batchUserIds">
    </form>

    <div class="cr-batch-toolbar d-none mb-3" id="crBatchToolbar">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAllUsers" aria-label="Select all users">
            </div>
            <span class="text-muted small" id="crSelectedCount">0 selected</span>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Batch Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" data-action="approve">Approve Selected</a></li>
                    <li><a class="dropdown-item" href="#" data-action="reject">Reject Selected</a></li>
                    <li><a class="dropdown-item" href="#" data-action="suspend">Suspend Selected</a></li>
                    <li><a class="dropdown-item" href="#" data-action="unsuspend">Unsuspend Selected</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="cr-table-wrap">
        @if ($users->isNotEmpty())
            <table class="cr-table table-cards">
                <thead>
                    <tr>
                        <th class="cell-checkbox"><input type="checkbox" id="selectAllUsersHeader" aria-label="Select all users on this page"></th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Active</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr data-user-id="{{ $user->id }}">
                        <td class="cell-checkbox"><input type="checkbox" class="user-checkbox" value="{{ $user->id }}" name="user_ids[]"></td>
                        <td data-label="User">
                            <strong>{{ $user->name }}</strong><br>
                            <small class="text-muted">{{ $user->email }}</small>
                        </td>
                        <td data-label="Role">
                            <span class="badge bg-secondary">{{ $user->role->label() }}</span>
                        </td>
                        <td>
                            @php
                                $statusClasses = [
                                    'pending' => 'tag-amber',
                                    'approved' => 'tag-green',
                                    'rejected' => 'tag-red',
                                    'suspended' => 'tag-gray',
                                ];
                                $statusClass = $statusClasses[$user->account_status] ?? 'tag-gray';
                            @endphp
                            <span class="cr-pill {{ $statusClass }}">{{ \App\Enums\AccountStatus::tryFrom($user->account_status)?->label() ?? ucfirst($user->account_status) }}</span>
                        </td>
                        <td data-label="Active">
                            <form action="{{ route('users.toggle-active', $user) }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="toggle_active" {{ $user->is_active ? 'checked' : '' }} onchange="this.closest('form').submit()">
                                    <label class="form-check-label">{{ $user->is_active ? 'Active' : 'Inactive' }}</label>
                                </div>
                            </form>
                        <td data-label="Last Login">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never' }}</td>
                        <td>
                            <div class="cr-actions">
                                <button type="button" class="cr-actions-menu-btn" aria-expanded="false" aria-haspopup="menu" aria-label="Actions">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="1"></circle>
                                        <circle cx="12" cy="5" r="1"></circle>
                                        <circle cx="12" cy="19" r="1"></circle>
                                    </svg>
                                </button>
                                <div class="cr-actions-menu" id="actionsMenu{{ $user->id }}" role="menu" aria-label="User actions">
                                    <a href="{{ route('users.edit', $user) }}" class="cr-actions-menu-item" role="menuitem">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Edit
                                    </a>
                                    @if ($user->account_status === 'suspended')
                                        <button type="button" class="cr-actions-menu-item suspend-action" role="menuitem" data-bs-toggle="modal" data-bs-target="#unsuspendModal{{ $user->id }}" style="background-color: white; border: none;">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                            Unsuspend
                                        </button>
                                    @else
                                        <button type="button" class="cr-actions-menu-item suspend-action" role="menuitem" data-bs-toggle="modal" data-bs-target="#suspendModal{{ $user->id }}" style="background-color: white; border: none;">
                                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                            Suspend
                                        </button>
                                    @endif
                                    <a href="{{ route('users.devices', $user) }}" class="cr-actions-menu-item" role="menuitem">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                                        Devices
                                    </a>
                                    @can('delete', $user)
                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="js-confirm-delete"
                                                  data-confirm-title="Delete user {{ $user->name }}?"
                                                  data-confirm="<strong>{{ $user->name }}</strong> will be archived before deletion. Accounts with citations, payments, clamping/impounding history, releases, appeals or assigned reports cannot be deleted.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cr-actions-menu-item text-danger" role="menuitem" style="background-color: white; border: none; width: 100%;">
                                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>

                            {{-- Suspend / Unsuspend Modal (per-row) --}}
                            @if ($user->account_status === 'suspended')
                                <div class="modal fade" id="unsuspendModal{{ $user->id }}" tabindex="-1" aria-labelledby="unsuspendModalLabel{{ $user->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="unsuspendModalLabel{{ $user->id }}">Unsuspend User</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('users.unsuspend', $user) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <p>Are you sure you want to unsuspend <strong>{{ $user->name }}</strong>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Unsuspend</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="modal fade" id="suspendModal{{ $user->id }}" tabindex="-1" aria-labelledby="suspendModalLabel{{ $user->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="suspendModalLabel{{ $user->id }}">Suspend User</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('users.suspend', $user) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <p>Are you sure you want to suspend <strong>{{ $user->name }}</strong>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-warning">Suspend</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Reject Modal (per-row) --}}
                            <div class="modal fade" id="rejectModal{{ $user->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $user->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="rejectModalLabel{{ $user->id }}">Reject User</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('users.reject', $user) }}" method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <p>Are you sure you want to reject <strong>{{ $user->name }}</strong>?</p>
                                                <div class="mb-3">
                                                    <label for="rejectReason{{ $user->id }}" class="form-label">Reason (required)</label>
                                                    <textarea class="form-control" id="rejectReason{{ $user->id }}" name="reason" rows="3" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Reject</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="cr-empty">No users found.</div>
        @endif
    </div>

    {{-- Pagination Footer --}}
    <div class="cr-table-footer">
        <span>Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} entries</span>
        {{ $users->withQueryString()->links() }}
    </div>

    {{-- Batch Reject Modal --}}
    <div class="modal fade" id="batchRejectModal" tabindex="-1" aria-labelledby="batchRejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="batchRejectModalLabel">Reject Selected Users</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('users.batch') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="user_ids" id="batchRejectUserIds">
                    <div class="modal-body">
                        <p>Are you sure you want to reject the selected users?</p>
                        <div class="mb-3">
                            <label for="batchRejectReason" class="form-label">Reason (required)</label>
                            <textarea class="form-control" id="batchRejectReason" name="reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject All</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Filters panel (Payments-style)
    const filterBtn = document.getElementById('crFilterBtn');
    const filterPanel = document.getElementById('crFilterPanel');
    const applyBtn = document.getElementById('crApplyBtn');

    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
            filterPanel.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!filterBtn.contains(e.target) && !filterPanel.contains(e.target)) {
                filterBtn.setAttribute('aria-expanded', 'false');
                filterPanel.classList.remove('open');
            }
        });

        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                const role = document.getElementById('crRoleSelect').value;
                const status = document.getElementById('crStatusSelect').value;
                const url = new URL(window.location.href);
                if (role) url.searchParams.set('role', role); else url.searchParams.delete('role');
                if (status) url.searchParams.set('account_status', status); else url.searchParams.delete('account_status');
                window.location.href = url.toString();
            });
        }
    }

    // Actions menu toggle (per-row)
    document.querySelectorAll('.cr-actions-menu-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const menu = this.closest('tr').querySelector('.cr-actions-menu');
            const expanded = this.getAttribute('aria-expanded') === 'true';
            // Close all OTHER menus (not this one)
            document.querySelectorAll('.cr-actions-menu.open').forEach(m => {
                if (m !== menu) {
                    m.classList.remove('open');
                    const mb = m.closest('tr').querySelector('.cr-actions-menu-btn');
                    if (mb) mb.setAttribute('aria-expanded', 'false');
                }
            });
            this.setAttribute('aria-expanded', !expanded);
            menu.classList.toggle('open');
        });
    });

    // Close actions menus on click outside
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.cr-actions')) {
            document.querySelectorAll('.cr-actions-menu.open').forEach(m => {
                m.classList.remove('open');
                const mb = m.closest('tr').querySelector('.cr-actions-menu-btn');
                if (mb) mb.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // Search debounce (300ms)
    const searchInput = document.querySelector('.cr-search-box input[name="search"]');
    let searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                searchInput.closest('form').submit();
            }, 300);
        });
    }

    // Escape key closes dropdowns/panels
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (filterPanel) { filterPanel.classList.remove('open'); filterBtn?.setAttribute('aria-expanded', 'false'); }
            document.querySelectorAll('.cr-actions-menu.open').forEach(m => {
                m.classList.remove('open');
                const mb = m.closest('tr').querySelector('.cr-actions-menu-btn');
                if (mb) mb.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // Batch actions toolbar
    const selectAllHeader = document.getElementById('selectAllUsersHeader');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const batchToolbar = document.getElementById('crBatchToolbar');
    const selectedCount = document.getElementById('crSelectedCount');
    const batchForm = document.getElementById('usersBatchForm');
    const batchActionInput = document.getElementById('batchAction');
    const batchUserIdsInput = document.getElementById('batchUserIds');
    const batchRejectModal = document.getElementById('batchRejectModal');
    const batchRejectUserIdsInput = document.getElementById('batchRejectUserIds');

    function updateBatchToolbar() {
        const checked = document.querySelectorAll('.user-checkbox:checked');
        const count = checked.length;
        if (count > 0) {
            batchToolbar.classList.remove('d-none');
            selectedCount.textContent = count + ' selected';
        } else {
            batchToolbar.classList.add('d-none');
        }
        if (selectAllHeader) selectAllHeader.checked = count > 0 && count === userCheckboxes.length;
    }

    if (selectAllHeader) {
        selectAllHeader.addEventListener('change', function () {
            userCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBatchToolbar();
        });
    }

    userCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBatchToolbar);
    });

    document.querySelectorAll('#crBatchToolbar [data-action]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const action = this.dataset.action;
            const checked = document.querySelectorAll('.user-checkbox:checked');
            const ids = Array.from(checked).map(cb => cb.value);

            if (ids.length === 0) return;

            if (action === 'reject') {
                batchRejectUserIdsInput.value = ids.join(',');
                const modal = new bootstrap.Modal(batchRejectModal);
                modal.show();
            } else {
                batchActionInput.value = action;
                batchUserIdsInput.value = ids.join(',');
                batchForm.submit();
            }
        });
    });

    // Row action confirmations
    document.querySelectorAll('.cr-pill-btn[data-confirm]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endpush