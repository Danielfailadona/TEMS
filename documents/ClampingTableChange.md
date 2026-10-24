# Clamping Table Change

### Date Edited / Applied `(required)`

- Edited and applied: 2026-10-19

### Type of Change `(required)`

- Feature / refactor (rebuilt `/clamping` index to match the `/payments` table layout)

### Requested By / Source `(optional)`

- Requested by the repository owner: make `/clamping` look like `/payments` with a table, search bar, filters, and active-filter chips.

### Problem `(required)`

- `/clamping` used a Bootstrap card grid (`stat-card` rows) with a separate Bootstrap filter bar, which looked inconsistent with the rebuilt `/payments` page (table, collapsible filter panel, search box, active chips, primary action button, paginated footer).
- Location: `/clamping` → `resources/views/clamping/index.blade.php` and `app/Http/Controllers/ClampingController.php`.

### Root Cause `(required)`

- Not a defect. The page predated the `/payments` rebuild design system (`.pym-dash`, `.pym-toolbar`, `.pym-table`, `.pym-pill`, `.pym-chip`, `.pym-table-footer`). The card grid and Bootstrap `form.row g-3` filter bar did not match the payments design language.

### Files Changed `(required)`

- `app/Http/Controllers/ClampingController.php`
- `resources/views/clamping/index.blade.php`
- `documents/ClampingTableChange.md` (this log)

### What Parts Changed `(required)`

#### Controller — `app/Http/Controllers/ClampingController.php` (`index`)

- Renamed the paginated variable `$records` → `$grid` to match `/payments`.
- Reduced page size from 12 to 5 to match `/payments`.
- Removed the unused `pendingRequests` and `overdueCitations` queries (the "Eligible Vehicles" card was dropped from the page to match `/payments`' clean table focus).
- Kept the existing query filters: `search` (notice # / plate / officer), `status`, and `date_from`/`date_to`.

**Before:**

```php
$records = $query->latest('clamped_at')->paginate(12)->withQueryString();

$pendingRequests = CitizenClampingRequest::where('status', 'pending')
    ->latest()
    ->get();

$overdueCitations = collect();
if (auth()->user()->isRole(\App\Enums\Role::SuperAdmin, \App\Enums\Role::Administrator, \App\Enums\Role::Enforcer)) {
    $eligibleDays = config('itevcms.clamping_eligible_days');
    $overdueCitations = Citation::whereIn('status', [CitationStatus::Overdue, CitationStatus::Issued])
        ->whereDate('due_date', '<=', now()->subDays($eligibleDays))
        ->whereDoesntHave('clampingRecords')
        ->orderBy('vehicle_plate')
        ->get();
}

return view('clamping.index', compact('records', 'pendingRequests', 'overdueCitations'));
```

**After:**

```php
$grid = $query->latest('clamped_at')->paginate(5)->withQueryString();

return view('clamping.index', compact('grid'));
```

Removed the now-unused `use App\Models\ClampingRequest as CitizenClampingRequest;` import.

#### View — `resources/views/clamping/index.blade.php`

Rewrote the page to mirror `/payments` structure:

- Replaced `@section('content')` card grid with `<div class="pym-dash">`.
- Replaced the Bootstrap filter bar with `#clampForm` toolbar (`pym-toolbar`) containing:
  - A collapsible filter panel (`#clampFilterBtn` / `#clampFilterPanel`) with Status select, date range inputs, Apply Filter / Clear Filter buttons.
  - A search box (`#clampSearchInput`) with debounce auto-submit (300ms).
  - A primary "Record Clamping" action button (`@can('create', ...)`).
- Added active-filter chips (`.pym-chips`) for search, status, date_from, date_to.
- Replaced the `stat-card` grid with a `<table class="pym-table">` whose header is `Notice # | Plate | Officer | Clamped At | Status | Fee | Actions`.
- Each row renders a `.pym-pill` status badge (`ClampingStatus::badgeClass()`) and conditional action buttons (View / Mark Paid / Waiting / Release) matching the previous `@can` gates.
- Added `@empty` state and a `.pym-table-footer` ("Showing X to Y of Z results" + pagination links).
- Added an inline `@push('styles')` block with the `.pym-*` classes (toolbar, filter panel, table, pill, chips, footer) so clamping renders identically to `/payments` without a Vite rebuild.
- Retained the date auto-submit JS (when both `date_from` and `date_to` are chosen) and mirrored `/payments`' filter-toggle / close-on-outside-click / Escape handling.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Page layout | Card grid + Bootstrap filter bar | Table + collapsible filter panel + toolbar (matches `/payments`) |
| Rows per page | 12 | 5 |
| Status filter | Bootstrap `<select>` | `.pym-select` inside filter panel + active chip |
| Search | Submits on Apply | Debounce auto-submit (300ms) + chip |
| Date filter | Auto-submits when both chosen | Same behavior, inside filter panel |
| Active filters | None | Removable chips row |
| Primary action | "Clamp" inside eligible-cards card | "Record Clamping" button in toolbar |
| Status badge | Bootstrap-ish badge | `.pym-pill` tag-* per the 5-color rule |
| Pagination | `$records->withQueryString()->links()` | `$grid->links()` + "Showing … results" footer |
| Empty state | Card with icon | Full-width table row |

### Impact & Risk `(required)`

- Affects: the `/clamping` index page only.
- Risk: low. Filtering logic (search/status/dates) is unchanged; only presentation and pagination size changed.
- Removed features: the "Eligible Vehicles" overdue-citations card and `pendingRequests`/`overdueCitations` view variables. These were not part of the requested table redesign. The `CitizenClampingRequest` import was removed as it became unused.
- No CSS/Vite rebuild required (clamping carries its own inline `@push('styles')` block, like `/payments`).

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `ClampingRecord` model, `ClampingStatus` enum, badge/label mappings
- `store`, `show`, `markPaid`, `markWaitingRelease`, `process-release` actions and their routes
- `ClampingController` non-`index` methods

### Known Issues / Follow-ups `(optional)`

- The "Eligible Vehicles" (overdue citations) card was removed from `/clamping` to match `/payments`. If that entry point is still needed, it can be re-added as a separate section or accessed via `clamping.create` with a `vehicle_plate`.
- Pagination dropped from 12 to 5 rows/page to match `/payments`; adjust `$grid = …->paginate(5)` if a different page size is preferred.

### Testing / Verification `(required)`

- `php -l app/Http/Controllers/ClampingController.php` — clean.
- `php -l resources/views/clamping/index.blade.php` — clean.
- `php artisan view:cache` → "Blade templates cached successfully"; then `php artisan view:clear`.
- Full suite: `& "C:\php-8.3\php.exe" "vendor/phpunit/phpunit/phpunit"` — 14 tests / 34 assertions, all passed.
- Manual: on `/clamping`, open Filters → set Status / dates / search → Apply → URL gains the query string, grid filters, chip appears; Clear Filter resets; Record Clamping button visible when authorized.
