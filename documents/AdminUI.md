# Admin Dashboard — KPI Card UI Changes

## Date Edited / Applied

- Edited and applied: 2026-08-24

---

## Type of Change

UI / feature enhancement (dashboard KPI card display)

---

## Requested By / Source

Dashboard design review — admin panel `/dashboard`

---

## Problem

The dashboard KPI cards (index.blade.php, lines 57–91) have two issues:
1. All three urgency KPIs — **Active Clamps**, **Unpaid Citations**, **Pending Appeals** — display their values in a single static color regardless of count. There is no visual urgency scaling.
2. The **"vs last week"** trend line always renders, including when it shows `0%` — which adds visual noise with no information.
3. **Total Citations** and **Payments Today** cards are visually identical in size/weight to the urgency cards, making it harder to scan the dashboard for the items that need action.

---

## Root Cause

The `$kpis` array in `dashboard/index.blade.php:59-66` is hard-coded with a single `tone` per card and no conditional visibility logic for the trend row. No CSS hierarchy exists between "primary" and "secondary" KPI cards.

---

## Files Changed

- `resources/views/dashboard/index.blade.php`
- `resources/css/app.css`

---

## What Parts Changed

### `resources/views/dashboard/index.blade.php`

**Before (lines 58–91):**

```php
<div class="row g-3 mb-4 animate-on-load row-cols-2 row-cols-md-3 row-cols-xl-5">
    @php
        $kpis = [
            ['icon' => 'bi-receipt',          'label' => 'Total Citations',  'value' => number_format($stats['total_citations']), 'tone' => 'primary', 'key' => 'total_citations'],
            ['icon' => 'bi-exclamation-circle','label' => 'Unpaid Citations', 'value' => number_format($stats['unpaid_citations']), 'tone' => 'warning', 'key' => 'unpaid_citations'],
            ['icon' => 'bi-cash-stack',        'label' => 'Payments Today',   'value' => '₱'.number_format($stats['revenue_today'], 2), 'tone' => 'success', 'key' => 'revenue_today'],
            ['icon' => 'bi-lock',              'label' => 'Active Clamps',    'value' => number_format($stats['active_clamps']), 'tone' => 'danger',  'key' => 'active_clamps'],
            ['icon' => 'bi-chat-square-text',  'label' => 'Pending Appeals',  'value' => number_format($stats['pending_appeals']), 'tone' => 'info',    'key' => 'pending_appeals'],
        ];
    @endphp
    @foreach ($kpis as $k)
        @php $t = $trends[$k['key']] ?? ['direction' => 'flat', 'percent' => 0]; @endphp
        <div class="col">
            <div class="card stat-card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                        <i class="bi {{ $k['icon'] }} text-{{ $k['tone'] }}"></i>
                        <span>{{ $k['label'] }}</span>
                    </div>
                    <div class="h3 mb-0 text-{{ $k['tone'] }}">{{ $k['value'] }}</div>
                    <div class="small mt-1">
                        @if ($t['direction'] === 'up')
                            <span class="trend-up"><i class="bi bi-arrow-up-short"></i>{{ $t['percent'] }}%</span>
                        @elseif ($t['direction'] === 'down')
                            <span class="trend-down"><i class="bi bi-arrow-down-short"></i>{{ $t['percent'] }}%</span>
                        @else
                            <span class="trend-flat"><i class="bi bi-dash"></i>0%</span>
                        @endif
                        <span class="text-muted">vs last week</span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
```

**After:**

```php
@php
    $kpis = [
        [
            'icon' => 'bi-receipt',
            'label' => 'Total Citations',
            'value' => number_format($stats['total_citations']),
            'tone' => 'primary',
            'key' => 'total_citations',
            'dim' => true,
        ],
        [
            'icon' => 'bi-exclamation-circle',
            'label' => 'Unpaid Citations',
            'value' => number_format($stats['unpaid_citations']),
            'tone' => 'warning',
            'key' => 'unpaid_citations',
            'colorRules' => [0 => 'success', 17 => 'warning', 29 => 'danger'],
        ],
        [
            'icon' => 'bi-cash-stack',
            'label' => 'Payments Today',
            'value' => '₱' . number_format($stats['revenue_today'], 2),
            'tone' => 'success',
            'key' => 'revenue_today',
            'dim' => true,
        ],
        [
            'icon' => 'bi-lock',
            'label' => 'Active Clamps',
            'value' => number_format($stats['active_clamps']),
            'tone' => 'danger',
            'key' => 'active_clamps',
            'colorRules' => [0 => 'success', 17 => 'warning', 29 => 'danger'],
        ],
        [
            'icon' => 'bi-chat-square-text',
            'label' => 'Pending Appeals',
            'value' => number_format($stats['pending_appeals']),
            'tone' => 'info',
            'key' => 'pending_appeals',
            'colorRules' => [0 => 'success', 17 => 'warning', 29 => 'danger'],
        ],
    ];
@endphp
@foreach ($kpis as $k)
    @php
        $t = $trends[$k['key']] ?? ['direction' => 'flat', 'percent' => 0];
        $val = $stats[$k['key']] ?? 0;
        $colorClass = 'text-' . ($k['tone'] ?? 'muted');
        if (!empty($k['colorRules'])) {
            $colorClass = 'text-muted';
            foreach ($k['colorRules'] as $threshold => $class) {
                if ($val >= $threshold) {
                    $colorClass = 'text-' . $class;
                }
            }
        }
    @endphp
    <div class="col">
        <div class="card stat-card border-0 h-100 {{ $k['dim'] ?? false ? 'dim' : '' }}">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                    <i class="bi {{ $k['icon'] }} text-{{ $k['tone'] }}"></i>
                    <span>{{ $k['label'] }}</span>
                </div>
                @php $valueSizeClass = ($k['dim'] ?? false) ? 'h6 mb-0' : 'h3 mb-0'; @endphp
                <div class="{{ $valueSizeClass }} {{ $colorClass }}">{{ $k['value'] }}</div>
                @if ($t['percent'] > 0 || $t['direction'] !== 'flat')
                <div class="small mt-1">
                    @if ($t['direction'] === 'up')
                        <span class="trend-up"><i class="bi bi-arrow-up-short"></i>{{ $t['percent'] }}%</span>
                    @elseif ($t['direction'] === 'down')
                        <span class="trend-down"><i class="bi bi-arrow-down-short"></i>{{ $t['percent'] }}%</span>
                    @else
                        <span class="trend-flat"><i class="bi bi-dash"></i>0%</span>
                    @endif
                    <span class="text-muted">vs last week</span>
                </div>
                @endif
            </div>
        </div>
    </div>
@endforeach
```

**Before (trend block — lines 78–87):**
```blade
<div class="small mt-1">
    <span class="trend-flat"><i class="bi bi-dash"></i>0%</span>
    <span class="text-muted">vs last week</span>
</div>
```

**After (trend block — wrapped in condition):**
```blade
@if ($t['percent'] > 0 || $t['direction'] !== 'flat')
    <div class="small mt-1">
        ...existing trend markup...
    </div>
@endif
```

---

### `resources/css/app.css`

**Added (near the end of the file or in the card section ~line 110):**

```css
.stat-card.dim .h3,
.stat-card.dim .h6 {
    font-size: 1.1rem;
    color: var(--itevcms-text-muted);
}
.stat-card.dim .card-body > div:first-child {
    opacity: 0.65;
}
```

---

## Behavior of the New Changes

| Scenario | Before | After |
|---|---|---|
| Active Clamps = 4 | Yellow (fixed `warning` tone) | **Green** (`text-success`) |
| Unpaid Citations = 22 | Yellow (fixed `warning` tone) | **Amber** (`text-warning`) |
| Pending Appeals = 35 | Purple (fixed `info` tone) | **Red** (`text-danger`) |
| Any urgency KPI = 0 | Red (fixed `danger`) | **Green** (`text-success`) |
| Trend line = 0% | Visibly renders `— 0% vs last week` | **Hidden entirely** |
| Total Citations value | `h3`, full color per `primary` tone | `h6`, muted gray (`text-muted`), smaller |
| Payments Today value | `h3`, full `success` green | `h6`, muted gray, smaller |

Thresholds applied:
- `0 – 16` → `text-success` (green)
- `17 – 28` → `text-warning` (amber)
- `29+` → `text-danger` (red)

---

## Impact & Risk

- **Affects:** `/dashboard` page for all authenticated staff roles.
- **Owner dashboard** (`owner.dashboard.blade.php`) — not affected; has its own KPI set.
- **Risk:** Low. Changes are purely presentational in one blade file and one CSS rule block. No controller, model, route, or database changes.
- **Cache note:** After deployment, run `php artisan view:clear` or hard-refresh (Ctrl+F5) to clear Blade and Vite cache.

---

## Database / Migration Impact

None.

---

## Untouched

- `DashboardController.php` — stats calculation unchanged
- All other controllers, models, routes, policies
- Charts section, GPS section, activity feed, Pending Work Queue
- Enforcer/Clamping Officer restricted sections

---

## Known Issues / Follow-ups

- Color thresholds (17 / 29) may need tuning based on real operational data after launch.

---

## Testing / Verification

- [ ] Visit `/dashboard` as Super Admin — verify 5 KPI cards render
- [ ] Confirm `Active Clamps` value color changes with varying counts (0 = green, 20 = amber, 30+ = red)
- [ ] Confirm `Unpaid Citations` and `Pending Appeals` follow same threshold logic
- [ ] Confirm `Total Citations` and `Payments Today` appear smaller and muted (gray)
- [ ] Confirm trend line is hidden when both `direction === 'flat'` and `percent === 0`
- [ ] Confirm trend line still shows when percent > 0
- [ ] Hard-reload (Ctrl+F5) to invalidate Vite asset cache
- [ ] Test on mobile viewport — cards should stack into 2-col grid (existing `row-cols-2`)