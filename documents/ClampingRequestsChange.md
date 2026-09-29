# Clamping Requests Changes

Log of changes made to the Clamping Requests feature
(`/clamping-requests`, `ClampingRequestController`, `resources/views/clamping-requests/*`).
Newest entries appear last.

---

## Index Rebuilt to Console Spec + Pending Stat Color States

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-29

### Type of Change `(required)`

- UI redesign (index only) + dynamic stat-card coloring + filter UX change.

### Requested By / Source `(optional)`

- Requested by user after comparing the live page with the static mockup
  `testPages/clamping_requests_rebuilt (1).html`:
  - turn the status pills into a Filters dropdown (like the mockup),
  - apply the dashboard's green/amber/red "state" coloring to the stat-card numbers
    (green = fine, amber = warning, red = a lot / urgent).

### Problem `(required)`

- The live `/clamping-requests` index used plain Bootstrap (default table sizes,
  outline pill links, colored stat-card borders) and did not match the console
  look used on `/citations`, `/payments`, `/clamping`, `/impounding`.
- Stat numbers were statically colored by type (pending=warning, approved=success,
  etc.) instead of by volume, so "a lot pending" looked the same as a quiet queue.

### Root Cause `(required)`

- The page predates the design-v1.1 console alignment pass.
- The reference dashboard already had a 3-tier state system
  (`resources/css/app.css` `.state-dot` / `.stat-card.state-*`) with thresholds of
  `>= 17` amber / `>= 29` red; those are tuned to citation volumes, so lowering to
  clamping-request scale was needed.

### Files Changed `(required)`

- `resources/views/clamping-requests/index.blade.php`
- `app/Http/Controllers/ClampingRequestController.php`
- `documents/ClampingRequestsChange.md` (this log, first entry)

### What Parts Changed `(required)`

#### `resources/views/clamping-requests/index.blade.php` (rewritten)

- Added scoped `.cr-dash` `@push('styles')` CSS (no npm build needed).
- Stats: kept the `row g-3 mb-4` grid of five cards; each card now uses the
  console `.stat-card` / `.stat-label` / `.stat-value` structure with the number
  on top. The Pending card gets `state-{{ $stats['pending_state'] }}` plus a
  `.state-dot` in its label (green `#10b981` / amber `#f59e0b` / red `#ef4444`).
  Total / Approved / Rejected / Resolved stay neutral.
- Control bar: replaced the outline pill links with a white rounded control bar
  containing a **Filters** button that opens a dropdown menu (`cr-filter-menu`)
  with All / Pending / Approved / Rejected / Resolved options — each is a real
  link to `clamping-requests.index` carrying the chosen `status` and preserving
  the current `search` (server-side filtering). "All" drops `status`.
  A hidden `status` input keeps the filter when the search box submits.
- Search box: styled `.cr-search-box` (38px icon well + input), blue magnifier,
  300ms debounce auto-submit (same pattern as `/clamping`).
- Table: console spec — `thead` 51px cells, `tbody tr` 78px, `td` 14px, fixed
  layout, requester name + phone sub-line, ellipsized location, `cr-date` /
  `cr-assigned` tints, custom status pill (`cr-badge`, colors per status state),
  and a **48x34 eye button** (`.cr-view-btn`, 20x20 icon) linking to the real
  show page.
- Footer: "Showing X to Y of Z results" + console-styled 38x30 pagination with
  `withQueryString()`; empty state row added.
- Behavior JS: dropdown open/close (click-outside + Escape) and search debounce.

#### `app/Http/Controllers/ClampingRequestController.php`

**Before:** stats array had only counts.
**After:** added `pending_state` derived from the pending count:

```php
$pending = $stats['pending'];
$stats['pending_state'] = $pending >= 4 ? 'red' : ($pending >= 1 ? 'amber' : 'green');
```

Thresholds: 0 = green (fine), 1-3 = amber (warning), 4+ = red (urgent).
Single tunable spot. Filtering, search, role scoping, and paginate(10) unchanged.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Status filter UI | Outline pill links (GET form) | Filters dropdown menu (links, server-side) |
| Stat card numbers | Static colors by type | Pending colored by volume (0/1-3/4+ = green/amber/red); others neutral |
| Request table | Bootstrap defaults | 51px header / 78px rows / 14px cells |
| Row action | "View" text button | 48x34 eye button linking to show page |
| Pagination | Bootstrap links | Console 38x30 links, 10/page |

### Impact & Risk `(required)`

- Affects: `/clamping-requests` index only. Low risk — server-side search/status
  and the show-page workflow (approve / reject / assign / resolve) are unchanged.
- Enforcer / Clamping Officer role scoping is untouched (view only).

### Database / Migration Impact `(optional)`

- None.

### Untouched `(optional)`

- `ClampingRequestController` search/status filters, role scoping, `paginate(10)`.
- `/clamping-requests/{id}` show page and its approve/reject/assign/resolve actions.
- Models, policies, routes, `layouts.app`, `app.css`, `resources/css/app.css`.

### Known Issues / Follow-ups `(optional)`

- Hard refresh (Ctrl+F5) required to see the new styles.
- Thresholds currently fixed at `1-3` amber / `4+` red; adjust the single line in
  the controller if volumes change.

### Testing / Verification `(required)`

- `php -l app/Http/Controllers/ClampingRequestController.php` — clean.
- `php -l resources/views/clamping-requests/index.blade.php` — clean.
- `php artisan view:clear` + `view:cache` — all compile.
- Headless render probe (authenticated admin, at `pending=0`): `cr-dash`,
  `crFilterButton`, `crFilterMenu` + 5 options, `status=pending` links present,
  `height: 51px`, `tbody tr { height: 78px }`, 14px cells, `width: 48px;
  height: 34px` eye button, `stat-card state-green` (pending 0), `cr-badge`,
  `crResultCount` — all YES. Amber/red states not present at 0 pending.