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

---

# 2026-10-11 — Tiered Admin Redesign + Count-Based 3-State "Needs attention" RAG

> This section supersedes the 2026-08-24 KPI-card approach documented above. The
> flat 5-card KPI grid with `colorRules`/`dim` was replaced by the tiered
> "console" layout, and the KPI colour logic was reworked from fixed tones to a
> whole-set, count-based 3-state RAG. This document is the primary record for the
> admin dashboard KPI card UI.

## Date Edited / Applied

- Edited and applied: 2026-10-11 (working tree; not yet committed)

## Type of Change

- UI redesign (tiered layout) + KPI colour-logic change. View/markup + one CSS
  rule only. No controller, route, model, policy, migration, or dependency
  changes.

## Requested By / Source

- Requested by user after comparing live dashboards with the static mockup
  `testPages/dashboard-full-redesign.html`; scope narrowed to the **Admin
  dashboard only** (FrontDesk excluded). Colour-logic follow-up requested by user
  after review.

## Problem

1. The dashboard used a flat 5-card KPI row + 2x2 charts + a utility row, not the
   mockup's tiered layout (Needs attention → Pending work → Overview → Trends →
   Activity → Tools).
2. The "Needs attention" cards used a **binary** colour rule
   (`0 = green`, `>0 = amber`); the red state was never used, so there was no
   severity signal and no way to distinguish 1 item from 100.
3. The colored `.state-dot` was the **only** status cue (fails WCAG 1.4.1 "Use of
   Color").

## Root Cause

- The tiered CSS layer already existed in `resources/css/app.css`
  (`.section-label`, `.tier1-row`, `.two-col`, `.queue-section`, `.stat-card.state-*`,
  `.panel`, `.qa-btn`, etc.) but no view consumed it. The view was still the old
  Bootstrap card grid.
- The attention state was computed with a single boolean expression, so the
  `state-red` CSS rules (already present) were dead code.

## Files Changed

- `resources/views/dashboard/index.blade.php`
- `resources/css/app.css` (backgrounds, quiet-card flex, responsive stacking,
  mockup-parity; from the tiered redesign — see `FrontDeskChangesRequest.md`)

## What Parts Changed

### `resources/views/dashboard/index.blade.php` — tiered layout

Replaced the flat KPI grid with the tiered console. Sections: **Needs attention**
(3 `.stat-card.state-*`) → **Pending work** (`.queue-section`, gated `!Cashier`) →
Enforcer GPS (unchanged) → **Overview** (2 `.stat-card--quiet`) → **Trends**
(two `.two-col` rows of `.panel`) → **Activity** (`.panel`, `!Enforcer && !Cashier`)
→ **Tools** (`.panel` + `.qa-btn` + zone map, `!Cashier`).

### `resources/views/dashboard/index.blade.php` — Needs attention colour logic

**Before:**

```php
$attention = [
    ['icon' => 'bi-lock', 'label' => 'Active Clamps', 'value' => $stats['active_clamps'], 'key' => 'active_clamps'],
    ['icon' => 'bi-chat-square-text', 'label' => 'Pending Appeals', 'value' => $stats['pending_appeals'], 'key' => 'pending_appeals'],
    ['icon' => 'bi-exclamation-circle', 'label' => 'Unpaid Citations', 'value' => $stats['unpaid_citations'], 'key' => 'unpaid_citations'],
];
// ...
$state = $a['value'] > 0 ? 'state-amber' : 'state-green';
```

**After:**

```php
$attention = [
    ['icon' => 'bi-lock', 'label' => 'Active Clamps', 'value' => $stats['active_clamps'], 'key' => 'active_clamps', 'red' => 4],
    ['icon' => 'bi-chat-square-text', 'label' => 'Pending Appeals', 'value' => $stats['pending_appeals'], 'key' => 'pending_appeals', 'red' => 10],
    ['icon' => 'bi-exclamation-circle', 'label' => 'Unpaid Citations', 'value' => $stats['unpaid_citations'], 'key' => 'unpaid_citations', 'red' => 50],
];
// ...
$state = $a['value'] === 0
    ? 'state-green'
    : ($a['value'] >= $a['red'] ? 'state-red' : 'state-amber');
$stateText = ['state-green' => 'Clear', 'state-amber' => 'Monitor', 'state-red' => 'Action needed'][$state];
```

**Accessibility cue** (visible non-colour status word; dot marked decorative):

```blade
<span class="state-dot" aria-hidden="true"></span>
<i class="bi {{ $a['icon'] }}"></i>
{{ $a['label'] }}
<span class="state-text">{{ $stateText }}</span>
```

### `resources/views/dashboard/index.blade.php` — inline styles

Added to the existing `@push('styles')` block (no `app.css` edit, no Vite rebuild
needed):

```css
.state-text { margin-left:auto; font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; opacity:0.85; }
```

## Why count-based (not the newest/oldest item)

A single item's timestamp cannot represent a set of items:

- newest item (`max(created_at)`) → a fresh item **masks** older ones → always green;
- oldest item (`min(created_at)`) → ancient items dominate → always red.

Counting items considers the **whole set** and is not masked, so the colour always
reflects every open item. See Appendix E for the SLA/age-based alternative.

## Behaviour of the New Changes

| Scenario | Before | After |
|---|---|---|
| Active Clamps = 0 | Green | **Green** — "Clear" |
| Active Clamps = 2 | Amber | **Amber** — "Monitor" |
| Active Clamps = 5 | Amber | **Red** — "Action needed" |
| Pending Appeals = 10 | Amber | **Red** — "Action needed" |
| Unpaid Citations = 60 | Amber | **Red** — "Action needed" |
| Status conveyed by | colour dot only | colour + uppercase status word |
| Layout | flat 5-card KPI grid | tiered console sections |
| Backend / data | — | unchanged |

Thresholds applied (amber = any value below the red cutoff, excluding 0):

| KPI | Green | Amber | Red |
|---|---|---|---|
| Active Clamps | 0 | 1–3 | ≥ 4 |
| Pending Appeals | 0 | 1–9 | ≥ 10 |
| Unpaid Citations | 0 | 1–49 | ≥ 50 |

## Impact & Risk

- **Affects:** `/dashboard` for admin-type roles (SuperAdmin, Administrator, and
  the Cashier/Enforcer variants). FrontDesk excluded.
- **Risk:** low-to-medium — view-only, but the dashboard is high-traffic and
  role-dependent; all `isRole`/`@can` gates, Chart.js init, GPS JS and the zone
  map were preserved.
- **Cache note:** `.state-red` / `.state-dot` were already present in the built
  Vite bundle, so **no rebuild is required** for this state change; a hard refresh
  (Ctrl+F5) is still recommended after deploy.

## Database / Migration Impact

- None.

## Untouched

- `DashboardController.php` (view selection + all data keys), routes, models,
  policies, enums, JS (`$recentActivity`/`$zoneMapData`), `render.*`, config.
- `resources/views/dashboard/frontdesk.blade.php` (excluded by decision).

## Known Issues / Follow-ups

- The red cutoffs (4 / 10 / 50) are operational placeholders and may need tuning
  against real volumes after launch.
- The **Pending work** queue dots are still 2-state (`.queue-count.zero`/`.some`);
  they could be upgraded to the same 3-state treatment for consistency (not done).
- The 2026-08-24 thresholds (17 / 29) no longer apply; superseded by this section.

## Testing / Verification

- [ ] `php artisan view:cache` compiles the Blade (real syntax check) then
      `php artisan view:clear`.
- [ ] Confirm all three states are reachable (0 = green, below cutoff = amber,
      ≥ cutoff = red) for each KPI.
- [ ] Confirm the visible "Clear/Monitor/Action needed" word renders.
- [ ] Confirm directive/tag balance unchanged and no new controller/query calls.
- [ ] Hard-reload (Ctrl+F5) on `/dashboard` for Admin, Cashier and Enforcer.

---

## Appendix E — Future: SLA / breach-based RAG (internet-recommended)

> Full research/reference version: `documents/DashboardAdminKPI.md`.

The count-based rule above is intentionally simple. The industry-recommended
model for work queues is **time/SLA-based**: colour by how many items have
**breached their SLA**, not by one item's age.

**Principle (RAG + status indicators):**
- Green = on target / no action; Amber = caution / at risk; Red = critical /
  action required.
- Use **concrete per-KPI thresholds**; when several items roll up to one
  indicator, show the **highest-attention** colour.
- **Never rely on colour alone** — pair with text/icon (WCAG 1.4.1; HPE: at least
  3 of 4 cues — colour + icon + shape + text).

**Proposed aggregation for each KPI:**

```php
// Citations — reuse the existing Overdue status (due_date < now(), 30-day SLA
// via config('itevcms.citation_overdue_days')).
$citationBreaches = Citation::where('status', CitationStatus::Overdue)->count();

// Appeals — age vs SLA (ServiceNow-style 0–3 / 4–7 / >7 day buckets).
$appealBreaches = Appeal::whereIn('status', [AppealStatus::Submitted, AppealStatus::UnderReview])
    ->where('submitted_at', '<', now()->subDays(7))
    ->count();

// Clamps — age vs SLA (tight, holding a vehicle).
$clampBreaches = ClampingRecord::where('status', ClampingStatus::AwaitingPayment)
    ->where('clamped_at', '<', now()->subHours(72))
    ->count();
```

Then colour each card by `breachCount` (e.g. `0` = green, `1..N` = amber,
`> N` = red), or use a **ratio** (`breaches / total`) for receivables.

**Caveats:**
- Unpaid Citations is a **balance/stock**, not a queue. If legacy debt stays open
  for months it would sit **red permanently** → alarm fatigue. Use a ratio
  (e.g. green <10%, amber 10–25%, red >25%) or a generous SLA for this KPI.
- This path requires a **controller change** (queries above) to expose the counts;
  the current implementation is deliberately view-only.

**Sources:** see Appendix F (full list with URLs).

---

## Appendix F — Research notes: what "the internet" recommends

Condensed, cited notes gathered while designing the colour logic. Use these when
revisiting the thresholds or building the SLA version (Appendix E).

### F.1 RAG status meaning (Red / Amber / Green)

| Colour | Meaning | Action |
|---|---|---|
| Green | On / above target; acceptable | No action |
| Amber | Caution / at risk; a concern | Monitor; action may be required |
| Red | Critical problem | Immediate action / escalation |

- RAG provides a high-level health summary; thresholds should be **defined, not
  guessed**.
- Source: ClearPoint, *Establish RAG statuses for KPIs* —
  https://www.clearpointstrategy.com/blog/establish-rag-statuses-for-kpis
- Source: Institute of Project Management, *RAG Status in Project Management* —
  https://instituteprojectmanagement.com/blog/rag-status-in-project-management

### F.2 How to set thresholds (the numbers)

- Use **concrete numeric thresholds per KPI** with a **tolerance band**:
  - "higher is better": Green = at/above target; Amber = within ~5–10% below;
    Red = >10% below.
  - "lower is better" (backlogs, overdue counts): **invert** the bands.
- Set bands from a baseline / historic median, and **review them regularly**;
  thresholds are operational, not permanent.
- Source: ClearPoint, *KPI dashboard best practices* —
  https://www.clearpointstrategy.com/blog/kpi-dashboard-best-practices
- Source: IBM, *Monitoring status through KPIs* (green = acceptable, yellow =
  caution, red = critical, gray = insufficient data) —
  https://www.ibm.com/docs/en/ioc/1.6.0?topic=data-monitoring-status-through-kpis

### F.3 Aggregating many items into one indicator

- When several items roll up into one status, display the **highest-attention**
  colour (if any item is red, show red).
- Source: Carbon Design System, *Status indicators* —
  https://preview.carbondesignsystem.com/building-blocks/core/patterns/status-indicators

### F.4 Work queues / backlogs → time- or SLA-based thresholds

This is the recommended upgrade path in Appendix E.

- **Jira Service Management**: colour reflects *time remaining* — green while
  within SLA, **amber when <30 min to breach**, **red when breached** (and a red
  cross for a completed-but-breached SLA). SLAs also show an icon (clock/pause)
  in addition to colour.
  - https://confluence.atlassian.com/servicemanagementserver101/sla-display-formats-and-time-frames-1455887549.html
  - https://support.atlassian.com/jira-service-management-cloud/docs/what-are-slas-and-how-do-i-view-them-in-my-service-project
- **Tungsten Automation TotalAgility**: configurable SLA thresholds on a work
  queue — Green until the amber threshold, Amber within the risk window, **Red
  once past the target**. Example: expected duration 2 h → amber at 1 h from due,
  red 30 min past due.
  - https://docshield.tungstenautomation.com/KTA/en_US/7.8.0-dpm5ap0jk8/help/TA/All_Shared/Workflow/c_actpropsla.html
- **ServiceNow (aging-ticket best practice)**: bucket by age **0–3 / 4–7 / >7
  days**, prioritise and escalate the **oldest first**, and fire notifications when
  an SLA is **75% consumed**.
  - https://www.servicenow.com/community/developer-forum/please-suggest-some-best-practices-to-manage-ageing-tickets-and/m-p/3115056
- **Atlassian Analytics**: "Request management overview" tracks "SLA breached %"
  as a headline metric.
  - https://support.atlassian.com/analytics/docs/request-management-overview-dashboard-template

### F.5 Receivables / balances → age buckets (AR aging)

- Standard receivables aging buckets are **0–30 / 31–60 / 61–90 / >90 days**.
  Useful if Unpaid Citations is treated as a balance rather than a queue (see the
  Appendix E caveat about permanent red).

### F.6 Accessibility — never colour alone

- **WCAG 2.1 SC 1.4.1 "Use of Color"**: colour must not be the *only* visual means
  of conveying information. Pair with text labels, shapes, or icons.
  - https://www.w3.org/WAI/WCAG21/Understanding/use-of-color.html
  - https://www.w3.org/WAI/wcag-curric/gid3-0.htm ("Don't rely on color alone")
- **HPE Design System — Status indicator**: compose a status from **colour +
  icon + shape + content**, and keep **at least 3 of the 4** present to satisfy
  WCAG 2.1. Always pair colour with other elements.
  - https://design-system.hpe.design/templates/status-indicator
- **Contrast**: text ≥ **4.5:1**; non-text/graphical objects ≥ **3:1**; avoid
  red–green-only encodings (problematic for colour-vision deficiency).
  - https://www.gooddata.ai/docs/cloud/create-dashboards/accessibility
  - https://prod.ada.nv.gov/siteassets/adanew.nv.gov/content/training/ada-wcag-checklist-for-power-bi-dashboards.pdf
  - https://www.cms.gov/files/document/tipcolorusedassolemethodtoconveymeaning.pdf
- Note: our current card shows a colour dot **plus** the visible word
  "Clear / Monitor / Action needed", which satisfies the "not colour alone" rule.

### F.7 How this maps to the current implementation

| Recommendation | Current state (2026-10-11) |
|---|---|
| Concrete per-KPI thresholds | ✅ done (4 / 10 / 50) |
| Amber within a tolerance band | ✅ implied (0 < value < red) |
| Don't rely on colour alone | ✅ visible status word added |
| SLA/time-based for queues | ⏳ not done — see Appendix E (needs controller change) |
| Highest-attention roll-up | n/a (one card per metric) |
| Tune against baseline | ⏳ pending real volume data |