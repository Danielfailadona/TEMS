# Citations Change Log

Change documentation for the Citations system. Follow the template in
`documents/documentation_Reference.md`. Append new entries at the end, newest last.

---

## Fix Offscreen MapLibre Controls on Citation Show Page

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-21

### Type of Change `(required)`

- Bug fix

### Requested By / Source `(optional)`

- Reported by user at `/citations/10`
- Element: `.maplibregl-control-container` (appeared below `.maplibregl-canvas`)

### Problem `(required)`

- The map zoom/navigation controls on the citation show page appeared offscreen
  (below the map canvas). The MapLibre control container stacked in normal
  document flow instead of overlaying the canvas.
- Location: `/citations/{id}` (Location Map card, id `citation-map`)

### Root Cause `(required)`

- `resources/views/citations/show.blade.php` loaded MapLibre's JavaScript only
  (`https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.js`) and never its
  stylesheet. MapLibre's own CSS is what gives `.maplibregl-control-container`
  its overlay positioning (`position: absolute; inset: 0`) on top of the
  canvas. Without it, the control container flowed below the canvas (and the
  canvas could also lose its intended positioning) - hence offscreen controls.
- Every other map page (zones, dashboard, tracking, clamping-requests) already
  links `maplibre-gl.css`; the citation show page was the only one missing it.

### Files Changed `(required)`

- `resources/views/citations/show.blade.php`
- `documents/CitationsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/citations/show.blade.php`

Added the official MapLibre stylesheet link inside `@push('styles')`, gated by
the existing coordinates `@if` (line 6), before the view's own `<style>`:

```html
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css">
```

No JS, controller, route, or markup changes. The `<link>` mirrors the exact
version/URL already used on the zones/dashboard/tracking pages.

### Behavior of the New Changes `(required)`

| Aspect | Before | After |
|---|---|---|
| `.maplibregl-control-container` | normal flow, below canvas (offscreen) | absolute overlay on top of canvas |
| Navigation control (top-right) | offscreen / not usable | rendered inside the map's top-right corner |
| Map canvas/tiles | could mis-position | proper MapLibre default sizing/positioning |

### Impact & Risk `(required)`

- Affects: `/citations/{id}` pages that have `latitude`/`longitude` captured
  (the map card only; pages without coordinates load neither CSS nor JS).
- Risk: low - external CDN stylesheet already used app-wide for identical
  MapLibre versions; view-only change. If the CDN is unavailable the page
  still renders (the map canvas just returns to pre-fix behavior).

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Map configuration/JS logic (intentionally unchanged)
- Citation model/controller/routes, print/ticket/payment pages
- Other pages' map styling

### Known Issues / Follow-ups `(optional)`

- Browsers may cache the page; a hard reload (Ctrl+F5) shows the fix.
- The link is live-injected only when the citation has coordinates; no code
  duplication with the other pages was added elsewhere.

### Testing / Verification `(required)`

- `php -l resources/views/citations/show.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/citations/10`: zoom/+/+/ compass controls appear top-right
  inside the map over the canvas; the map pans/zooms normally; the detail
  overlay (bottom-left) still works.

---

## Citations Index Redesigned to Dashboard Mockup

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-21

### Type of Change `(required)`

- Feature / UI redesign (styling only, no behavior change)

### Requested By / Source `(optional)`

- Requested by user to match `testPages/citations_dashboard_native_html.html`
  (static design mockup for the Citations console page). Live page: `/citations`.

### Problem `(required)`

- The live citations list used a plain Bootstrap form + `table-hover` card and
  did not match the mockup's console look (Filters dropdown, search icon,
  "Issue Citation" pill, bordered fixed-width table, custom pagination footer).

### Root Cause `(required)`

- No deliberate styling; the page relied on layout defaults. The mockup defined
  its own design system (toolbar/filter/search/table/footer) that was never
  implemented in the Blade view.

### Files Changed `(required)`

- `resources/views/citations/index.blade.php`
- `documents/CitationsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/citations/index.blade.php`

Full rewrite of the page body to mirror the mockup. New `@push('styles')` block
with page-scoped `.cit-dash` CSS (toolbar, filters dropdown, search box,
table widths/borders, 97px rows, status pill, eye-view pill, custom pagination,
responsive media queries).

- **Toolbar** (`form#citForm` GET): hidden `status` input; "Filters" dropdown
  listbox built from `CitationStatus::cases()` (selecting submits the form);
  search box whose **blue search icon is a `type="submit"` button** (applies
  search + current status, same effect as the removed `btn btn-outline-secondary`
  "Filter" button); "＋ Issue Citation" styled pill kept as the real
  `@can('create')` link to `citations.create`.
- **Table**: `thead` 51px, `#e8edf2` 2px row borders, **97px rows**, fixed
  widths `21.5/19.5/10/11/13.5/10/8%`, 14px text; status pill uses
  `badgeClass()` (`tag-*` rule colors) with pill radius; actions column is an
  eye-icon pill link to `citations.show`.
- **Footer**: "Showing X to Y of Z results" from paginator + `$citations->links()`
  restyled via CSS to the mockup page-button look; empty-state preserved.
- **`@push('scripts')`**: status dropdown toggle/close (Escape + outside click),
  option label swaps onto the Filters button, and a 300ms debounced search
  auto-submit (Enter also submits natively).

**Before (summary):** plain Bootstrap GET form (`search` input, `status` select,
Filter button), Bootstrap `table-hover` card, default `$citations->links()`.

**After:** mockup-styled toolbar/table/footer, all server-side behavior intact.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Apply search | "Filter" button (btn-outline-secondary) submitted | blue search icon (submit) + Enter + 300ms debounce auto-submit |
| Filter by status | native `<select>` in GET form | "Filters" dropdown listbox (still GET submit, same param `status`) |
| Issue citation | `btn btn-primary` text-end link | pill "＋ Issue Citation" link |
| Row action | "View" outline text button | eye-icon pill link to show page |
| Table look | default Bootstrap hover table | fixed widths, 51px header, 97px rows, `#e8edf2` borders |
| Status badge | `badge` w/ tag-* colors | pill-shaped w/ same tag-* rule colors |
| Pagination | default Bootstrap pagination | square page-button style + "Showing X to Y of Z" |

### Impact & Risk `(required)`

- Affects: only the `/citations` index page (CSS is scoped under `.cit-dash`).
- Risk: low - no controller/route change; GET params `search`/`status` and
  pagination query-string behavior are unchanged. JS is page-scoped via `@push`.
- Debounced auto-submit fires 300ms after typing stops; clicking the blue search
  icon submits immediately.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `CitationController@index` query/search/filter/pagination logic
- Routes, models, layout shell (`layouts.app`), `app.css`
- The `/citations/create` and `/citations/{id}` pages

### Known Issues / Follow-ups `(optional)`

- Browsers may cache the page; a hard reload (Ctrl+F5) shows the new UI.
- Selecting a status or searching reloads the page (server-driven) — expected,
  since the dataset is paginated to 10 rows server-side (client-side filtering
  would only cover the current page).

### Testing / Verification `(required)`

- `php -l resources/views/citations/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Rendered the view headlessly (authenticated admin, MessageBag shared) and
  verified: toolbar, table, status menu, search submit icon, pill, eye link,
  footer, issue link, and `tag-*` classes present.
- Manual QA (hard refresh): search via icon/Enter/debounce, each status filter,
  pagination links, "Showing X to Y of Z", empty state, rule-colored pills.

---

## Index: 78px Rows + 5-Per-Page So Pagination Shows

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-29

### Type of Change `(required)`

- UI/UX adjustment (row height) + pagination page-size change (behavior)

### Requested By / Source `(optional)`

- Reported by user at `/citations`. Two requests:
  1. Make the `<tr>` rows `height: 78px;` (they were `97px`).
  2. The pagination design was "not in there" — no pagination controls visible.

### Problem `(required)`

1. Table rows were taller than wanted (97px) — user asked for 78px.
2. The page already had a styled pagination footer (`$citations->links()` +
   "Showing X to Y of Z" + `.cit-dash .pagination` CSS), but it only rendered
   when there were more than 10 citations (the per-page size). With only 7
   citations in the database, `hasPages()` was `false`, so the pagination
   design never appeared.

### Root Cause `(required)`

- Row height was a hardcoded CSS value (`97px`) in the `.cit-dash` scoped styles.
- Pagination hidden as a data artifact: `paginate(10)` with 7 records is a
  single page → no page buttons render.

### Files Changed `(required)`

- `resources/views/citations/index.blade.php`
- `app/Http/Controllers/CitationController.php`
- `documents/CitationsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/citations/index.blade.php` — row height

**Before:**
```css
.cit-dash .cit-table tbody tr { height: 97px; border-bottom: 2px solid #e8edf2; }
```

**After:**
```css
.cit-dash .cit-table tbody tr { height: 78px; border-bottom: 2px solid #e8edf2; }
```

#### `app/Http/Controllers/CitationController.php` — page size

**Before:**
```php
->latest('issued_at')
    ->paginate(10)
    ->withQueryString();
```

**After:**
```php
->latest('issued_at')
    ->paginate(5)
    ->withQueryString();
```

No other markup/CSS/JS changes — the existing pagination design (square page
buttons, active/disabled states, "Showing X to Y of Z" footer) was already in
place and now has enough pages to appear.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Row height | 97px | 78px |
| `/citations` with 7 records (10/page) | single page, no pagination shown | 2 pages, styled pagination footer visible |
| Pagination query-string / filters | preserved | preserved (unchanged) |

### Impact & Risk `(required)`

- Affects: `/citations` index only (CSS scoped under `.cit-dash`) and the page
  size on that list. `search`/`status` filters and `withQueryString()` behavior
  unchanged.
- Risk: low — visual height change plus a smaller page size; the pagination
  design was already shipped, so nothing else is affected.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Citation model, routes, search/filter logic, status dropdown, badges, eye-view
  pill, empty state, show/create pages, `app.css`.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache the page; a hard reload (Ctrl+F5) shows the new row height.
- Pagination will again be hidden if citations ever drop to 5 or fewer.

### Testing / Verification `(required)`

- `php -l app/Http/Controllers/CitationController.php` — no syntax errors.
- `php artisan view:clear` + `view:cache` — all Blade templates compiled.
- Headless render probe (authenticated admin): with 7 citations, the footer now
  renders `page-link` elements (pagination appears, 2 pages) and the stylesheet
  contains `height: 78px`.