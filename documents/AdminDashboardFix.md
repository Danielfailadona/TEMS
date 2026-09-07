# Admin Dashboard Redesign & Error Fixes

---

## Admin Dashboard Redesign, SVG Charts, and Rendering/Bug Fixes

### Date Edited / Applied `(required)`

- Edited and applied: 2026-08-30

### Type of Change `(required)`

- Bug fix + refactor (dashboard template rewrite: Chart.js → SVG, 3-tier KPI layout, layout/CSS fixes)

### Requested By / Source `(optional)`

- Reported by user at `/dashboard` (GET). Symptoms: parse errors, division-by-zero,
  non-numeric value errors, and vertically elongated/broken placement.

### Problem `(required)`

The admin dashboard (`/dashboard`) was failing or rendering incorrectly in several
ways:

- **Parse error** — `syntax error, unexpected token "-"` from Blade mangling CSS
  variables in SVG attributes (`stroke="{{ --itevcms-border }}"`).
- **Division by zero** — `$maxCount` was `0`/`null`, breaking the Citations chart.
- **"Trying to access array offset on int"** — chart loop treated values as arrays.
- **"A non-numeric value encountered"** — chart loop used a string as a number.
- **Duplicated content** — KPI 5-card block, GPS section, and Analytics grid each
  rendered twice (leftover from earlier edits).
- **Broken layout** — stray `</div>`, wrong state colors, unequal card widths,
  undefined CSS classes, and a `justify-center` typo caused vertical elongation
  and non-existent placement.

### Root Cause `(required)`

1. **Blade + CSS variable syntax** — Blade interprets `{{ --name }}` as a PHP
   decrement, not a CSS variable. Correct form is `var(--name)`.
2. **Wrong collection access** — `$citationsByMonth` is a Laravel Collection
   keyed by `"Y-m"` strings with integer counts (from
   `->groupBy(...)->map->count()`). The chart loop iterated it as
   `@foreach (...) as $index => $month` where `$index` is a **month string**, not
   a numeric index, and `$month` is the **integer count**.
3. **Incorrect max call** — `$citationsByMonth->max('count')` returns `null`
   because the collection holds plain integers (no `'count'` key).
4. **Stray `</div>`** — an extra closing tag after the greeting header closed the
   main content container early.
5. **Incorrect state CSS** — `.state-green/.amber/.red` set `background-color` on
   the whole card instead of only the dot and value.
6. **Missing CSS** — `.stat-label`, `.stat-value`, `.stat-trend`, `.queue-header`,
   `.queue-left`, `.queue-title`, `.queue-sub`, and `.stat-card` padding were
   referenced in HTML but undefined; cards had no `flex: 1`; `.queue-count` had
   `justify-center:` (typo for `justify-content:`).

### Files Changed `(required)`

- `resources/views/dashboard/index.blade.php`
- `resources/css/app.css`

### What Parts Changed `(required)`

#### `resources/views/dashboard/index.blade.php`

**Citations by Month chart — correct data access:**

**Before:**

```php
@foreach ($citationsByMonth as $index => $month)
    $maxCount = $citationsByMonth->max('count');
    $barWidth = ($chartWidth - ($gap * ($barCount - 1))) / $barCount;
    $barHeight = ($month['count'] / $maxCount) * $chartHeight;
    $x = $startX + $index * ($barWidth + $gap);
    $monthLabel = $monthNames[$index] ?? '';
@endforeach
```

**After:**

```php
$maxCount = $citationsByMonth->max() ?: 0;
$barWidth = ($barCount > 0) ? ($chartWidth - ($gap * ($barCount - 1))) / $barCount : 0;

@foreach ($citationsByMonth as $index => $month)
    $safeMaxCount = max(1, $maxCount);
    $monthValue = is_numeric($month) ? $month : 0;
    $barHeight = ($monthValue / $safeMaxCount) * $chartHeight;
    $x = $startX + $loop->index * ($barWidth + $gap);
    $monthLabel = $index;
@endforeach
```

**SVG attributes — CSS variables:**

**Before:** `stroke="{{ --itevcms-border }}"`

**After:** `stroke="var(--itevcms-border)"`

**Structure — removed stray `</div>` after header; removed duplicate header, KPI
5-card block, GPS section, and Analytics grid (239 lines total).**

#### `resources/css/app.css`

**State system — dot/value color instead of card background:**

**Before:** `.state-green { background-color: #10b981; }`

**After:** `.state-green .state-dot { background: #10b981; }` and
`.state-green .stat-value { color: #10b981; }` (same pattern for `.amber`/`.red`).

**Equal-width flex cards:**

```css
.tier1-row > .stat-card,
.tier3-row > .stat-card,
.two-col > .stat-card {
    flex: 1 1 0;
    min-width: 0;
}
```

**Added structure/queue classes:** `.stat-card { padding: 18px 20px; }`,
`.stat-label`, `.stat-value`, `.stat-trend`, quiet-card variants, `.queue-header`,
`.queue-left`, `.queue-title`, `.queue-sub`.

**Fixed typo:** `.queue-count` `justify-center:` → `justify-content: center`.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Load `/dashboard` | Parse / div-by-zero / non-numeric errors | Page renders without errors |
| Citations chart | Blank or errors | Bars render with month labels |
| KPI section | Duplicate blocks / broken | Single 3-tier layout |
| Card layout | Vertically elongated, solid color blocks | Equal-width cards, correct dot/value colors |
| Queue section | Missing styles | Proper dividers, titles, subtitles |
| Analytics grid | Rendered twice | Single instance |

### Impact & Risk `(required)`

- Affects: `/dashboard` only (admin/enforcer/clamping-officer dashboard).
- Risk: hard refresh (`Ctrl+F5`) needed to bypass cached JS/CSS; other pages were
  not modified. Chart.js remains loaded globally (other pages use it) but its
  initialization calls were removed from this page only.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `app/Http/Controllers/DashboardController.php` (data calculations unchanged)
- Routes, middleware, models, database queries
- Sidebar/topbar layout (`resources/views/layouts/app.blade.php`)
- Chart.js library (still loaded globally)

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old JS/CSS; a hard reload (`Ctrl+F5`) is needed to see the fix.

### Testing / Verification `(required)`

- `php -l resources/views/dashboard/index.blade.php` — no syntax errors
- `php artisan view:cache` — all Blade templates compiled successfully
- `php artisan view:clear` — compiled views cleared
- `npm.cmd run build` — assets rebuilt (new CSS hash `app-BpogXUQ0.css` emitted)

---

## Follow-up: Chart Line Origin Fix (Citations, Revenue, Appeals)

### Date Edited / Applied `(required)`

- Edited and applied: 2026-08-30

### Type of Change `(required)`

- Bug fix (chart rendering correctness)

### Requested By / Source `(optional)`

- Reported by user at `/dashboard`. Symptom: "Citations by Month" chart showed a 
  single dot instead of a line from origin; Revenue/Appeals charts also didn't 
  start from zero.

### Problem `(required)`

1. **Citations chart showed single dot** — When only 1 data point existed, the 
   chart rendered a single dot at the data point instead of drawing a line from 
   the origin (0 value) to that point.
2. **Revenue/Appeals charts** — Also didn't start lines from origin (0 value).
3. **Empty state charts** — Flat dashed line didn't start from origin (0 value).

### Root Cause `(required)`

The polyline for all three charts was built starting from the first data point 
instead of from the origin (0 value at x=30, y=100 in the SVG coordinate system). 
The reference design (dashboard-full-redesign (4).html) shows lines starting 
from the origin (y=100 = 0 value) and connecting to the first data point.

### Files Changed `(required)`

- `resources/views/dashboard/index.blade.php`

### What Parts Changed `(required)`

#### `resources/views/dashboard/index.blade.php`

**Citations by Month — line now starts from origin:**

**Before (polyline started at first data point):**
```php
$cPoints = [];
foreach ($cMonthVals as $idx => $month) {
    $px = ($cN > 1) ? 30 + ($idx * ($barWidth + $gap)) : 30;
    $py = 150 - $bottomMargin - $barHeight;
    $cPoints[] = $px . ',' . $py;
}
```

**After:**
```php
$cPoints = [];
// Start from origin (0 value at x=30, y=100)
$cPoints[] = '30,100';
foreach ($cMonthVals as $idx => $month) {
    $px = ($cN > 1) ? 30 + ($idx * ($barWidth + $gap)) : 30;
    $py = 150 - $bottomMargin - $barHeight;
    $cPoints[] = round($px) . ',' . round($py);
}
```

**Revenue Trend / Appeals Trend — same fix applied:**

**Revenue:**
```php
$rPoints = [];
$rPoints[] = '30,100';
foreach ($rMonthVals as $idx => $val) { ... }
```

**Appeals:**
```php
$aPoints[] = '30,100';
foreach ($aMonthVals as $idx => $val) { ... }
```

**Empty state charts (Revenue, Appeals, Citations):** Updated flat dashed line 
to start from origin (30,100) instead of hardcoded values.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Citations (1 data point) | Single dot at data point | Line from origin (0) to data point |
| Citations (2+ points) | Line from 1st point | Line from origin through all points |
| Revenue/Appeals (data) | Started at 1st point | Line from origin through all points |
| Empty state charts | Flat line at arbitrary position | Flat dashed line at 0 (from origin) |

### Impact & Risk `(required)`

- Affects: `/dashboard` Analytics charts only.
- Risk: Low — purely visual fix; no data changes.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Controller logic (`DashboardController.php`) — data calculations unchanged.
- Routes, middleware, models, database queries.
- Chart.js library remains loaded globally (needed by other pages) but 
  initialization calls removed from this page since we replaced canvases with SVG charts.

### Known Issues / Follow-ups `(optional)`

- None

### Testing / Verification `(required)`

- `php -l resources/views/dashboard/index.blade.php` — no syntax errors
- `php artisan view:cache` — all Blade templates compiled successfully
- `php artisan view:clear` — compiled views cleared
- `npm.cmd run build` — assets rebuilt (new CSS hash `app-CnfQI1qy.css` emitted)

---

## Follow-up: Overview Cards Demotion + Payments Today Currency Symbol

### Date Edited / Applied `(required)`

- Edited and applied: 2026-08-30

### Type of Change `(required)`

- Bug fix (visual hierarchy + currency formatting)

### Requested By / Source `(optional)`

- Reported by user at `/dashboard`. Symptoms: Overview cards (Total Citations, Payments Today) 
  looked identical to Tier 1 "Needs Attention" cards; Payments Today missing ₱ symbol.

### Problem `(required)`

1. **Overview cards (Tier 3) looked identical to Tier 1 cards** — same white background, 
   same font size, same padding, and they incorrectly showed trend indicators with 
   state colors. Per spec, they should be visually demoted: light gray background, 
   muted gray text, smaller font/padding, no trend indicators, no state colors.

2. **Payments Today missing ₱ symbol** — displayed `0.00` instead of `₱0.00`.

### Root Cause `(required)`

1. Tier 3 cards used the same `.stat-card` styling as Tier 1, including trend indicators 
   and state color logic, but spec requires them to be "quiet" — muted, smaller, no 
   trends, no state colors.
2. The Blade template for Payments Today used `{{ number_format($stats['revenue_today'], 2) }}` 
   without the `₱` prefix that other monetary values used.

### Files Changed `(required)`

- `resources/views/dashboard/index.blade.php`
- `resources/css/app.css`

### What Parts Changed `(required)`

#### `resources/views/dashboard/index.blade.php`

**Tier 3 Overview cards — removed trends, removed state dots, added ₱ symbol:**

**Before (Total Citations):**
```blade
<div class="stat-card stat-card--quiet">
    <div class="stat-label"><span class="stat-dot"></span>Total Citations</div>
    <div class="stat-value">{{ number_format($stats['total_citations']) }}</div>
    @php $t = $trends['total_citations'] ?? ... @endphp
    @if (...) <div class="stat-trend">...@endif
</div>
```

**After:**
```blade
<div class="stat-card stat-card--quiet">
    <div class="stat-label">Total Citations</div>
    <div class="stat-value">{{ number_format($stats['total_citations']) }}</div>
</div>
```

**Before (Payments Today):**
```blade
<div class="stat-value">{{ number_format($stats['revenue_today'], 2) }}</div>
```

**After:**
```blade
<div class="stat-value">₱{{ number_format($stats['revenue_today'], 2) }}</div>
```

**Trend blocks completely removed from both Tier 3 cards.**

#### `resources/css/app.css`

**Quiet card styling — visual demotion:**

**Before:**
```css
.stat-card--quiet {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 12px 16px;
}
.stat-card--quiet .stat-value {
    font-size: 18px; font-weight: 600; color: #6b7280;
}
.stat-card--quiet .stat-label { font-size: 12px; }
```

**After:**
```css
.stat-card--quiet {
    background-color: #F9FAFB;
    border: 1px solid #E5E7EB;
    padding: 10px 14px;
}
.stat-card--quiet .stat-label { font-size: 12px; color: #6B7280; }
.stat-card--quiet .stat-value {
    font-size: 18px; font-weight: 600; color: #6B7280;
}
.stat-card--quiet .stat-trend { display: none; }
```

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Overview cards | Same size/color as Tier 1, showed trends | Light gray bg, muted gray text, smaller font/padding, no trends |
| Payments Today value | `0.00` | `₱0.00` |
| Tier 3 trend lines | Visible with state colors | Hidden completely |

### Impact & Risk `(required)`

- Affects: `/dashboard` only (Tier 3 Overview cards).
- Risk: Low — purely presentational changes; no data/logic changes.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Controller logic (`DashboardController.php`)
- Tier 1/2 KPI cards, Analytics charts, Recent Activity, Queue section
- Chart.js library (still loaded globally for other pages)

### Known Issues / Follow-ups `(optional)`

- None

### Testing / Verification `(required)`

- `php -l resources/views/dashboard/index.blade.php` — no syntax errors
- `php artisan view:cache` — all Blade templates compiled successfully
- `php artisan view:clear` — compiled views cleared
- `npm.cmd run build` — assets rebuilt (new CSS hash `app-BpogXUQ0.css` emitted)

---

## Chart Overflow / Thinner Charts & Top Violation Redesign & Users Ordering

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-04

### Type of Change `(required)`

- Bug fix + UI redesign (4 issues)

### Requested By / Source `(optional)`

- Reported by user at `/dashboard` and `/users`
  - /dashboard → Citations/Revenue/Appeals chart cards, "Top Violation Types" card
  - /users → `#users-table`

### Problem `(required)`

1. **Exceeding text** — In the chart cards ("Citations by Month"/"Revenue Trend"/"Appeals Trend"), value and month labels overflow the card width and get cut off at the right edge.
2. **Fatter line / big text** — Chart number fonts too large and the gap between grid lines too wide.
3. **Top Violation Types** — Bars had a fixed 80px width (not a full track), and rows with `count = 0` were removed entirely by the query. Wanted the `progress_bar_5.html` rounded track/fill style and to list all violation types statically (position fixed regardless of count; empty ones show a gray bar).
4. **Users ordering** — `/users` table did not show newest users first within each status group.

### Root Cause `(required)`

1. **Overflow** — SVG charts used a fixed `viewBox` (300 wide) with the last data point at `x ≈ 290` and `text-anchor="middle"` labels (e.g. `₱15000`), so long labels extended past the 300px box and were clipped at the card edge.
2. **Bulk** — `stroke-width="2"`, point radius `r=3`, value `font-size=11`, and a 45px gap between the three horizontal gridlines (`y=10/55/100`).
3. **Violations** — Controller filtered out zero-count types and ordered by count; template rendered a fixed 80px `div` with a 5px solid-blue bar instead of a full rounded track+fill.
4. **Users** — Query ordered by status then `name` (alphabetical), not by newest creation.

### Files Changed `(required)`

- `resources/views/dashboard/index.blade.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/UserController.php`

### What Parts Changed `(required)`

#### `resources/views/dashboard/index.blade.php` (3 chart sections + violation card)

**Chart geometry (Citations, Revenue, Appeals — data + empty states):**
- Plot span `260` → `240`, max x `290` → `270` so the last point/label stays inside the box.
- Gridlines `y=10/55/100` → `y=15/55/95` (tighter gaps); y-axis labels moved to `y=19/59/99`.
- Vert origin: polyline/circle baseline `100` → `95`; value mapping `(val/max)*90` → `(val/max)*80`.
- `stroke-width` 2 → 1.5; point `r` 3 → 2.5; value `font-size` 11 → 9; month `font-size` 10 → 9; value label offset `-8` → `-7`; month label `y=116` → `112`.
- Revenue value labels abbreviated: `₱{{ round($val) }}` → `₱{{ round($val/1000) }}k` when `>= 1000` (else plain).

**Top Violation Types card:**
- Inline CSS `violation-bar` (5px solid `#2563eb`) and the fixed `width:80px` wrapper replaced with `violation-bar-track` (8px, `#e7ecfb`, `border-radius:999px`, `overflow:hidden`) + `violation-bar-fill` (gradient `#2f5bff→#4e7cff`, rounded, `transition:width .4s`) + `violation-count`.
- Template always renders the full list; empty-count rows keep the gray track with no fill.

#### `app/Http/Controllers/DashboardController.php`

**Before:**
```php
$topViolations = ViolationType::withCount(['citations' => fn ($q) => $q->where('issued_at', '>=', now()->subMonths(3))])
    ->orderByDesc('citations_count')
    ->take(10)
    ->get()
    ->filter(fn ($v) => $v->citations_count > 0)
    ->take(5)
    ->map(fn ($v) => ['name' => $v->name, 'count' => (int) $v->citations_count])
    ->values();
```

**After:**
```php
$topViolations = ViolationType::withCount(['citations' => fn ($q) => $q->where('issued_at', '>=', now()->subMonths(3))])
    ->orderBy('id')
    ->get()
    ->map(fn ($v) => ['name' => $v->name, 'count' => (int) $v->citations_count])
    ->values();
```

#### `app/Http/Controllers/UserController.php`

**Before:**
```php
->orderBy(\Illuminate\Support\Facades\DB::raw("CASE account_status WHEN 'pending' THEN 0 ... END"))
->orderBy('name')
```

**After:**
```php
->orderBy(\Illuminate\Support\Facades\DB::raw("CASE account_status WHEN 'pending' THEN 0 ... END"))
->orderBy('created_at', 'desc')
```

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Chart labels at right edge | Clipped/overflowed card width | Stay inside the chart box |
| Chart line & points | 2px line, r=3 dots, 11px labels | 1.5px line, r=2.5 dots, 9px labels |
| Gridline gap | 45px (y=10/55/100) | 40px (y=15/55/95) |
| Revenue label | `₱15000` | `₱15k` |
| Violation bar | 80px fixed bar, only non-empty types | Full-width rounded track + gradient fill, all types listed statically |
| Zero-count violation | Row hidden | Row stays in fixed position, gray track with no fill |
| /users order | status group, then alphabetical by name | status group preserved, newest created_at first within each group |

### Impact & Risk `(required)`

- Affects: `/dashboard` (3 chart cards + Top Violation Types) and `/users` (table order).
- Risk: Low–moderate. Chart geometry changed, so confirm visually. Violation card now always lists all types (up to all rows), which may add rows if more types are added. Users order changed — verify no test relies on alphabetical order.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `resources/css/app.css` global tokens and layout
- All other dashboard sections (KPI tiers, queue, activity, zone map)
- Other controllers/models/routes

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old JS/CSS; a hard reload (Ctrl+F5) is needed to see the fixes.

### Testing / Verification `(required)`

- `php -l resources/views/dashboard/index.blade.php` — no syntax errors
- `php artisan view:cache` — all Blade templates compiled successfully
- `npm.cmd run build` — assets rebuilt
- Visual QA on `/dashboard` (charts no longer overflow, thinner/tighter) and `/users` (newest first within status group)

---

## Unify Recent Activity, Appeals Trend & Top Violation Types to reference styles

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-04

### Type of Change `(required)`

- UI refactor (align component form/style to `testPages/tems_dashboard.html`)

### Requested By / Source `(optional)`

- Requested by user after comparing `testPages/tems_dashboard.html` (reference) with `/dashboard`.

### Problem `(required)`

The live `/dashboard` Recent Activity, Appeals Trend, and Top Violation Types used a smaller, Bootstrap-utility style that differed noticeably in form and style from the reference (`tems_dashboard.html`): activity icons were emoji in a faint-blue circle, violation bars were compact with gradient, and the appeals trend used a thin solid line.

### Root Cause `(required)`

The reference uses dedicated, larger component styles (16px labels, 14px track, 48px avatar circles with SVG line icons, thicker dashed purple trend line) while the live page used smaller inline/Bootstrap classes.

### Files Changed `(required)`

- `resources/views/dashboard/index.blade.php`

### What Parts Changed `(required)`

#### `resources/views/dashboard/index.blade.php`

**Top Violation Types — inline CSS (`.violation-*`):**
- Row: changed to reference layout `flex; gap:20px; padding:12px 0`, removed row `border-bottom` dividers.
- Added `.violation-label` (fixed `width:190px`, `font-size:16px`, color `#1f2937`).
- Track: `height:8px; background:#e7ecfb; border-radius:999px` → `height:14px; background:#e3e7f5; border-radius:7px`.
- Fill: gradient `#2f5bff→#4e7cff` → solid `#2563eb`.
- Count: `0.8rem #6b7280` → `.violation-count` fixed `width:24px`, right-aligned, `16px` weight 700 color `#1f2937`.

**Top Violation Types — markup:**
- Removed `$maxCount` calc and the `@if ($v['count'] > 0)` condition; every row now always renders a static `width:60%` fill (fixed size across all bars regardless of count). Label now uses `.violation-label`.

**Appeals Trend — inline SVG charts (data + empty state):**
- Line color `#7c3aed` → `#6d5bd0` (reference purple); `stroke-width="1.5"` → `"3"`.
- Data polylines: added `stroke-linecap="round"`; point dots `r="2.5"` → `"4"`; value text fill `#7c3aed` → `#6d5bd0`.
- Empty-state dashed line: `dasharray="4 4"` → `"2 14"` (round-cap), dot `r` 2.5 → 4.

**Recent Activity — activity icon (markup only):**
- Replaced the emoji glyph (map of `📄💳📝🔒`) with per-type inline SVG line icons:
  - citation → receipt outline; payment → cash/card; appeal → chat bubble; clamp → lock.
  - All icons: `width/height 20`, `viewBox 0 0 24 24`, `fill none`, `stroke #2563eb`, `stroke-width 1.8`, round caps/joins.
- The `.activity-icon` circle wrapper (size, faint-blue background) stays unchanged so only the icon stands out in blue.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Violation label | small inline `0.84rem`, no fixed width | 16px, fixed 190px width |
| Violation track | 8px, `#e7ecfb`, rounded pill | 14px, `#e3e7f5`, radius 7px |
| Violation fill | gradient `#2f5bff→#4e7cff`, proportional | solid `#2563eb`, static 60% for all rows |
| Violation row dividers | `border-bottom` between rows | no dividers (reference style) |
| Count | right edge, small `#6b7280` | fixed 24px right-aligned, 16px bold `#1f2937` |
| Appeals line | `#7c3aed` 1.5px thin | `#6d5bd0` 3px, round caps (empty: dashed 2 14) |
| Appeals dots | r 2.5 | r 4 |
| Activity icon | emoji in faint-blue circle | blue SVG line icon (stroke `#2563eb`) in unchanged circle |

### Impact & Risk `(required)`

- Affects: `/dashboard` — Top Violation Types card, Appeals Trend card, Recent Activity card.
- Risk: Low–moderate. Pure presentational changes. Visual QA recommended; confirm the thicker chart lines and blue icons render as expected.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `resources/css/app.css` (`.activity-icon` circle background/size unchanged)
- Citations/Revenue trend charts, KPI tiers, queue, zone map, controllers/models/routes

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old JS/CSS; a hard reload (Ctrl+F5) is needed to see the fixes.
- The violation fill is intentionally a static 60% width for all types (not proportional); counts are shown at the right.

### Testing / Verification `(required)`

- `php -l resources/views/dashboard/index.blade.php` — no syntax errors
- `php artisan view:cache` — all Blade templates compiled successfully
- `npm.cmd run build` — assets rebuilt
- Visual QA on `/dashboard` for the three updated cards

---

## Dynamic Violation Bars (proportional, hidden when 0)

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-04

### Type of Change `(required)`

- Bug fix / UI behavior change (Top Violation Types)

### Requested By / Source `(optional)`

- Reported by user at `/dashboard` → `.violation-bar-track` / `.violation-bar-fill`.

### Problem `(required)`

The Top Violation Types bars showed a blue fill even when a violation type had `count = 0`, and the width was hardcoded to a fixed 60% regardless of the actual count. The user wanted:
1. No blue fill when the count is 0 (only show the blue bar when the type has citations).
2. The numbers to come from the database list of violation types (e.g. "Illegal Parking", "Red Light Violation").

### Root Cause `(required)`

The template previously rendered `<div class="violation-bar-fill" style="width:60%;">` unconditionally for every row (a leftover from the earlier "static 60%" requirement), so it ignored the count entirely.

### Files Changed `(required)`

- `resources/views/dashboard/index.blade.php`

### What Parts Changed `(required)`

#### `resources/views/dashboard/index.blade.php` — Top Violation Types loop

**Before:**
```blade
@foreach ($topViolations as $v)
    <div class="violation-item">
        <span class="violation-label">{{ $v['name'] }}</span>
        <div class="violation-bar-track">
            <div class="violation-bar-fill" style="width:60%;"></div>
        </div>
        <span class="violation-count">{{ $v['count'] }}</span>
    </div>
@endforeach
```

**After:**
```blade
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
```

The count for each violation type already comes from the database via `DashboardController.php` (`ViolationType::withCount(...)->orderBy('id')`), keyed by name and count; no controller change was needed.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Violation with count = 0 | Blue bar at fixed 60% | No blue bar (gray track only) |
| Violation with count > 0 | Blue bar at fixed 60% | Blue bar sized proportionally to count (width = count / max count) |
| Data source | hardcoded 60% | counts from `violation_types` DB table via `$topViolations` |

### Impact & Risk `(required)`

- Affects: `/dashboard` — Top Violation Types card only.
- Risk: Low. Presentational/data-driven change; visual QA recommended.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Controller logic and query (`DashboardController.php`)
- `.violation-bar-track` / `.violation-bar-fill` CSS
- Other dashboard cards/sections

### Known Issues / Follow-ups `(optional)`

- Replaces the earlier "static 60% fill" behavior; bars are now proportional and hidden at 0.

### Testing / Verification `(required)`

- `php -l resources/views/dashboard/index.blade.php` — no syntax errors
- `php artisan view:cache` — all Blade templates compiled successfully
- `npm.cmd run build` — assets rebuilt
- Visual QA on `/dashboard` — zero-count types show empty gray bars, non-zero types show proportional blue bars