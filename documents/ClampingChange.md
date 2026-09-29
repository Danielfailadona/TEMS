# Clamping Change Log

Change documentation for the Vehicle Clamping system. Follow the template in
`documents/documentation_Reference.md`. Append new entries at the end, newest last.

---

## Index: Add Citations-Style Search Bar + Align Table Sizes

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-29

### Type of Change `(required)`

- Feature (search) + UI alignment (rows/fonts/pagination match `/citations`)

### Requested By / Source `(optional)`

- Requested by user at `/clamping`: add a search bar similar to `/citations` and
  check that the table matches it in `<tr>` height and font sizes.

### Problem `(required)`

1. `/clamping` had **no search** — only a plain Bootstrap `table-hover` card and
   a "Record Clamp" button in a `text-end` div.
2. The Clamping Records table did **not** follow `/citations`: rows used default
   Bootstrap padding (no 78px), fonts inherited the ~16px default (citations uses
   14px), the header wasn't 51px / 14px / weight 700, and pagination was the
   default Bootstrap `links()` style.

### Root Cause `(required)`

- The clamping index was an early Bootstrap page never given the scoped
  console-style design (`.cit-dash` / `.pay-dash` / `.imp-dash`) used by the
  other list pages, and its controller had no search handling.

### Files Changed `(required)`

- `app/Http/Controllers/ClampingController.php` (only `index()`)
- `resources/views/clamping/index.blade.php`
- `documents/ClampingChange.md` (new log, this entry)

### What Parts Changed `(required)`

#### `app/Http/Controllers/ClampingController.php`

Added a trimmed `search` filter and changed page size:

**Before:**
```php
$query = ClampingRecord::with(['officer', 'citation']);

$records = $query->latest('clamped_at')->paginate(10);
```

**After:**
```php
$query = ClampingRecord::with(['officer', 'citation']);

if ($search = trim($request->query('search'))) {
    $query->where(function ($inner) use ($search) {
        $inner->where('vehicle_plate', 'like', "%{$search}%")
            ->orWhere('notice_number', 'like', "%{$search}%")
            ->orWhereHas('officer', fn ($o) => $o->where('name', 'like', "%{$search}%"))
            ->orWhereHas('citation', fn ($c) => $c->where('citation_number', 'like', "%{$search}%"));
    });
}

$records = $query->latest('clamped_at')->paginate(5)->withQueryString();
```

Search matches: vehicle plate, notice number, officer name, citation number.

#### `resources/views/clamping/index.blade.php`

Full restyle with a page-scoped `.clp-dash` design mirroring `/citations`:

- **Toolbar** (`form#clpForm`, GET): search box (blue submit magnifier icon +
  text input) matching `.cit-search-box` style and placeholder
  "Search notice #, plate, or officer..."; "Record Clamp" link moved in as a red
  pill (`.clp-danger-btn`).
- **Clamping Records table**: renamed to `.clp-table` with `thead th` 51px /
  14px / weight 700, `tbody tr` 78px, `td` 14px, fixed column widths
  (22/20/18/15/14/11%), status badge as `.clp-badge` pill + existing
  `badgeClass()` colors, actions as an eye-icon pill (`.clp-view-btn`).
- **Footer**: "Showing X to Y of Z results" + `$records->links()` styled via
  `.clp-dash .pagination` exactly like citations (38x30px, 13px, radius 5px,
  `#176ff2` active).
- **`@push('scripts')`**: 300ms debounced auto-submit on the search input
  (Enter also submits natively).
- **Untouched (per user decision):** the Citizen Clamping Requests and Eligible
  Vehicles summary cards stay plain Bootstrap.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Search clamping records | not possible | search by notice #, plate, officer, citation # |
| Row height | default Bootstrap padding | 78px (matches citations) |
| Body / header font | ~16px inherited | 14px / 14px weight 700 |
| Header height | default | 51px |
| Pagination | default Bootstrap `links()` | citations-style 38x30px buttons |
| Page size | 10 | 5 (matches citations/payments) |

### Impact & Risk `(required)`

- Affects: `/clamping` index only — scoped `.clp-dash` CSS, controller `index()`.
- Risk: low. Existing sections (requests, eligible vehicles, create/show pages,
  `Report Clamp` routes) unchanged; GET `search` preserves the current page via
  `withQueryString()`. With 3 records, pagination stays hidden until data
  exceeds 5 rows.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Clamping create/show/store/update/delete + release/impounding flows
- Citizen Clamping Requests + Eligible Vehicles cards (kept plain Bootstrap)
- Policies, enums, models, routes, layouts, `app.css`

### Known Issues / Follow-ups `(optional)`

- Browsers may cache the page; a hard reload (Ctrl+F5) shows the new UI.
- With only 3 clamping records, the pagination bar won't appear until the list
  exceeds 5 rows.

### Testing / Verification `(required)`

- `php -l app/Http/Controllers/ClampingController.php` — clean.
- `php -l resources/views/clamping/index.blade.php` — clean.
- `php artisan view:clear` + `view:cache` — all compile.
- Headless render probe (authenticated admin): toolbar + search box present;
  CSS carries `tbody tr { height: 78px }`, `font-size: 14px`, and the 38px
  pagination; search query filters records.