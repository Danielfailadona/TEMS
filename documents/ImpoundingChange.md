# Impounding Change Log

Change documentation for the Impounding / Clamping system. Follow the template in
`documents/documentation_Reference.md`. Append new entries at the end, newest last.

---

## Missing `vehicle_releases` Table Crashes Impounding Show Page

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-21

### Type of Change `(required)`

- Bug fix (database schema restoration / migration)

### Requested By / Source `(optional)`

- Reported by user at `/clamping/create` after recording a clamp; error surfaced
  on `GET /impounding/2`

### Problem `(required)`

- Recording a clamp succeeded (`clamping_records` row created), but navigating to
  the impounding detail page threw:

  `SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "vehicle_releases" does not exist`

- Root path: `app/Http/Controllers/ImpoundingController.php:45` eager-loads the
  `release` relation, which queries table `vehicle_releases`.

### Root Cause `(required)`

- The table `vehicle_releases` did not exist in the (Supabase pgsql) database even
  though the migration `2026_06_13_000009_create_vehicle_releases_table.php` had
  run and was recorded in the `migrations` table (id 12, batch 1).
- The later cleanup migration `2026_06_16_034222_drop_vehicles_and_drivers_tables.php`
  cheer-called `Schema::dropIfExists('vehicle_releases')` in its `up()` (line 22),
  accidentally dropping the clamping-release table as collateral while removing the
  old `vehicles` / `drivers` schema. The create migration was already recorded, so a
  plain `php artisan migrate` could not recreate it.
- Rollback of the drop migration was not an option - its `down()` also restores the
  intentionally-removed `vehicle_id` / `driver_id` columns and `vehicles`/`drivers`
  tables.

### Files Changed `(required)`

- `database/migrations/2026_09_21_000000_recreate_vehicle_releases_table.php` (new)
- `documents/ImpoundingChange.md` (this log)

### What Parts Changed `(required)`

#### New migration `2026_09_21_000000_recreate_vehicle_releases_table.php`

- `up()`: `Schema::create('vehicle_releases', ...)` mirroring the original schema:
  `id`, unique `release_number`, `clamping_record_id` (FK to `clamping_records`),
  `released_by` (FK to `users`), nullable `notes`, `released_at`, `timestamps`.
- `down()`: `Schema::dropIfExists('vehicle_releases')`.
- Applied to the live pgsql connection via
  `php artisan migrate --path=database/migrations/2026_09_21_000000_recreate_vehicle_releases_table.php`
  (recorded as a new row in `migrations`, batch following the existing 39).

### Behavior of the New Changes `(required)`

| Aspect | Before | After |
|---|---|---|
| `vehicle_releases` table | absent (42P01) | exists with original schema |
| `/impounding/{id}` show page | 500 QueryException | renders; release section shows "—" when none |
| `/clamping/{id}`, `/owner/clamping` | would also fail on `release` | render normally |
| Future `migrate:fresh` | table dropped after create step | recreate migration runs last; table present |

### Impact & Risk `(required)`

- Affects: all pages that access `$clamping->release` (impounding show,
  clamping show, owner/clamping, print-release).
- Risk: low - new standard migration, only creates a currently-missing table; no
  existing data (table was already empty/dropped).

### Database / Migration Impact `(optional)`

- One new row in `migrations`. No existing tables/data touched. FKs point at
  existing `clamping_records` and `users` tables.

### Untouched `(optional)`

- `ImpoundingController` / `ClampingRecord` / `VehicleRelease` code (unchanged)
- CLI check tooling note: `migrate:status` / table-style artisan commands still
  crash with `Call to undefined function Termwind\ValueObjects\mb_strimwidth()`
  because the PHP CLI lacks the `mbstring` extension - unrelated to this change;
  `php -r` schema checks were used instead.

### Known Issues / Follow-ups `(optional)`

- Consider enabling `mbstring` in the local PHP CLI so artisan table commands work.
- The original drop migration (`2026_06_16_034222`) still contains the
  `dropIfExists('vehicle_releases')` line; it is left untouched because the
  recreate migration now runs after it.

### Testing / Verification `(required)`

- `php -l database/migrations/2026_09_21_000000_recreate_vehicle_releases_table.php`
- `php artisan migrate --path=database/migrations/2026_09_21_000000_recreate_vehicle_releases_table.php` -> DONE
- `Schema::hasTable('vehicle_releases')` -> `bool(true)`
- Re-ran the controller's eager-load set for `ClampingRecord` id 2
  (`officer`, `citation.violationType`, `citation.payment.cashier`, `release.releasedBy`)
  with `release` resolving to `NULL`; no exception.
- Manual QA: hard-refresh `/impounding/2` and `/clamping/2` - both pages load.

---

## Impounding Index Redesigned to Rebuilt Mockup (Dropdown Filters + Search)

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-21

### Type of Change `(required)`

- Feature / UI redesign (restyle + server-side search, tooling change)

### Requested By / Source `(optional)`

- Requested by user to match `testPages/impounding_rebuilt.html` (console-shell
  mockup for the Impounding page). Live page: `/impounding`.
- User decisions: turn the status pill buttons into a **dropdown**, add a
  **search bar** (blue magnifier icon on the left, click applies the filter
  including the selected status), **remove the Evidence column**, keep the
  footer entry count + pagination below, **all action buttons blue**, and
  action column = **View + Record Payment only** (follow the mockup).

### Problem `(required)`

- The live index used Bootstrap pill buttons for the status filter, had no
  search, an 8th Evidence column, mixed-color action buttons (success/warning),
  and extra Queue Release / Process Release row actions - none matching the
  mockup console look.

### Root Cause `(required)`

- `ImpoundingController@index` had no `search` handling; the view used layout
  defaults with no deliberate styling and no toolbar.

### Files Changed `(required)`

- `app/Http/Controllers/ImpoundingController.php` (only `index()`)
- `resources/views/impounding/index.blade.php`
- `documents/ImpoundingChange.md` (this log)

### What Parts Changed `(required)`

#### `app/Http/Controllers/ImpoundingController.php`

`index()` - adds a `search` filter and query-string persistence:

```php
$query->when($request->search, function ($q, $search) {
    $q->where(function ($inner) use ($search) {
        $inner->where('vehicle_plate', 'like', "%{$search}%")
            ->orWhere('notice_number', 'like', "%{$search}%")
            ->orWhereHas('citation.violationType', fn ($v) => $v->where('name', 'like', "%{$search}%"))
            ->orWhereHas('officer', fn ($o) => $o->where('name', 'like', "%{$search}%"));
    });
});

$records = $query->latest('clamped_at')->paginate(10)->withQueryString();
```

- Search targets: `vehicle_plate`, `notice_number` (direct columns), violation
  type name via `citation.violationType`, and officer name via `officer` (both
  relations -> `orWhereHas`). The existing `whereIn`/`where('status')` filter is
  unchanged.
- `->withQueryString()` keeps `?status=` and `?search=` across pagination.

#### `resources/views/impounding/index.blade.php`

Full rewrite with a page-scoped `.imp-dash` `@push('styles')` block (all CSS
scoped; no `app.css` change), modeled on the mockup:

- **Toolbar** (single GET `form#impForm`):
  - **Filters dropdown** - `Filters` button (114x36, caret) + menu with options
    Active / Awaiting Payment / Paid / Waiting Release / Released as buttons.
    Clicking an option sets the **hidden `status` input** and submits the form.
    "Active" = empty status (server default: awaiting + paid + waiting release).
  - **Search box** (flex 0 1 696px) - **blue magnifier icon on the left as a
    submit button**; clicking it (or Enter / 300ms debounce) submits `search`
    together with the hidden `status`. Placeholder "Search...".
- **Table** - **Evidence column removed** -> 7 columns with mockup widths
  `13.1/16.5/15.3/14/13.2/14/14%`, 53px header, **84px rows**, `#edf0f3`
  borders, 11px font, **bold plate**, muted `—` for missing violation.
  **Status badge keeps `badgeClass()`** (tag color-coding rule - not the
  mockup's all-red pill).
- **Actions (all blue)** - mockup-shaped row: **eye pill** (56x28, radius 16px,
  blue border) -> `impounding.show`; **Record Payment pill** (28px, blue
  border/text) opens the existing `payModal` (`@can('markPaid')`).
  **Removed**: Queue Release / Process Release row buttons and the
  `releaseModal` block (these remain on the show page).
- **Footer** - "Showing X to Y of Z results" + `$records->links()` styled as
  square page buttons (min 35px, 30px tall, radius 5px, current page filled
  blue, prev/next arrows); empty state retained.
- **Responsive** media queries (900 / 600) ported from the mockup.
- **`@push('scripts')`** - dropdown toggle / outside-click / Escape close,
  option-click submit, 300ms debounced search submit.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Filter by status | pill button links | Filters dropdown (server reload) |
| Search records | not possible | search box; submit via left magnifier icon / Enter / 300ms debounce |
| Search + filter combo | n/a | form submits both; pagination persists both |
| Evidence thumbnails | 8th column | column removed (still on show page) |
| Status colors | tag rule (badgeClass) | tag rule (badgeClass) - unchanged |
| Actions | mixed success/warning + View | blue eye + Record Payment pills only |
| Entry count | none | "Showing X to Y of Z results" in footer |
| Pagination | default Bootstrap | square page buttons, current filled blue |

### Impact & Risk `(required)`

- Affects: only the `/impounding` index page (scoped `.imp-dash`).
- Removing Evidence column and Queue/Process Release buttons from the index does
  **not** remove functionality - the show page still offers Record Payment,
  Pay Online, Queue for Release, Process Release, Print Release Order, and the
  evidence image.
- Risk: low - controller change is additive; behavior of plain `/impounding`
  unchanged (`$request->search` falsy before).

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `ImpoundingController` show/markPaid/markWaitingRelease/processRelease/print
  actions, `impounding/show.blade.php`, routes, models, status enums, layout
  shell, `app.css`, `payModal` markup (moved verbatim), `badgeClass()` colors

### Known Issues / Follow-ups `(optional)`

- Hard refresh (Ctrl+F5) needed to see the new UI (cached page).
- Search is server-driven; typing triggers a reload after 300ms.

### Testing / Verification `(required)`

- `php -l app/Http/Controllers/ImpoundingController.php`
- `php -l resources/views/impounding/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Headless render (authed admin, MessageBag, real ClampingRecord data):
  toolbar, filter button + 5 menu options, search submit icon, table card,
  eye pill, Record Payment pill, footer result count, pagination all present;
  no `releaseModal`, no Evidence `<th>` in output.
- Manual QA (hard refresh): `/impounding` - dropdown filters reload list and
  keep `?search=`, magnifier/Enter/debounce search work, pagination persists
  `?status=` + `?search=`, status badges keep rule colors, actions all blue,
  responsive wrap at 900/600px.

---

## Impounding Table 13px Fonts, 28x28 Eye, Pagination Styling Activated

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-21

### Type of Change `(required)`

- Styling fix (table type scale, eye button size, pagination styling activation)

### Requested By / Source `(optional)`

- Requested by user:
  1. Make all fonts inside the `/impounding` table 13px.
  2. Change `.imp-dash .imp-eye-btn` to 28px wide and 28px tall.
  3. Check whether the next-page button below the table is applied.

### Problem `(required)`

- Table fonts were 11px (header/body) and 9px (status pill, Record Payment pill).
- The eye button was 56x28.
- The square pagination styling was written in CSS **but never matched the
  markup**: the footer called `{{ $records->links() }}` bare, so no
  `.imp-pagination` element existed for the `.imp-dash .imp-pagination ...`
  selectors. The bootstrap-5 default pagination (and its own "Showing X to Y of
  Z results" text) rendered instead.

### Root Cause `(required)`

- Fonts: sizes hard-coded from the mockup (11px/9px); user wants 13px.
- Pagination: missing wrapper element around `$records->links()`; the bootstrap-5
  view also hides its numbered squares below 576px (shows text prev/next links).

### Files Changed `(required)`

- `resources/views/impounding/index.blade.php` (scoped CSS + footer markup)
- `documents/ImpoundingChange.md` (this log)

### What Parts Changed `(required)`

#### CSS (all inside the `@push('styles')` `.imp-dash` block)

- `thead th` font-size 11px -> **13px**
- `tbody td` font-size 11px -> **13px**
- `.imp-status` font-size 9px -> **13px**
- `.imp-payment-btn` font-size 9px -> **13px**
- `.imp-eye-btn` width 56px -> **28px** (height stays 28px; `border-radius:16px`
  on a 28px square renders as a circle)
- Added pagination companions:
  - `.imp-pagination .small.text-muted { display:none !important; }` - hides the
    bootstrap-5 built-in "Showing X to Y of Z results" (duplicated our counter)
  - `.imp-pagination .d-none.flex-sm-fill { display:flex !important; }` - keeps
    the numbered square buttons visible on `<576px`
  - `.imp-pagination .d-sm-none { display:none !important; }` - hides the
    bootstrap mobile prev/next text block

#### Footer markup

- `{{ $records->links() }}` wrapped in `<div class="imp-pagination">` so the
  existing square pagination selectors (`.imp-pagination .pagination`,
  `.page-link`, `.page-item.active`, `.disabled`) now actually apply.
- (The above three rules replaced one placeholder rule that matched nothing.)

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Table text | 11px header/body, 9px pills | all 13px |
| Eye button | 56x28 pill | 28x28 circle |
| Pagination look | unstyled bootstrap-5 default + duplicate "Showing" text | square buttons (min 35px, 30px tall, radius 5px), active filled blue, single result count, squares also on phones |

### Impact & Risk `(required)`

- Affects: only `/impounding` index.
- Note: the live DB currently has few clamping records (3 total, 1 in the
  default active set), so pagination may not appear at all until there are more
  than 10 records in the current filter - a data-volume matter, not a code issue.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Eye icon SVG, pill heights, action pills, dropdown/search, status tag colors,
  controller, modals, payments page (note: `/payments` has the same built-in
  "Showing" duplication awaiting the same fix if desired)

### Known Issues / Follow-ups `(optional)`

- A hard refresh (Ctrl+F5) is needed to see the new CSS.
- Payments index (`pay-results` + built-in "Showing…") still duplicates the
  count text; fix opportunistically in a future pass.

### Testing / Verification `(required)`

- `php -l resources/views/impounding/index.blade.php` - clean.
- `php artisan view:cache` / `view:clear`.
- Headless render (pagination forced with a simulated total of 25): output
  contains the `.imp-pagination` wrapper and 7 square page links (prev + 3 pages
  + next); `th`/`td`/`.imp-status`/`.imp-payment-btn` all show `font-size: 13px`;
  `.imp-eye-btn` shows `width: 28px; height: 28px`; all three pagination support
  rules present. Confirm the live page once record volume exceeds 10 rows.