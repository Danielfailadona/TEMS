# Teams Change Log

Change documentation for the Team Management system. Follow the template in
`documents/documentation_Reference.md`. Append new entries at the end, newest last.

---

## Apply Team Management HTML/CSS Demo Design to /teams

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-13

### Type of Change `(required)`

- Feature (UI redesign)

### Requested By / Source `(optional)`

- Requested by user; reference: `testPages/Team_Management_HTML_CSS_Demo.html`
- Applied to: `http://127.0.0.1:8000/teams`

### Problem `(required)`

- The `/teams` page used the app's default boxed layout: small padded stat cards,
  a wide GET form with a native status `<select>`, a "Has zones" checkbox, and an
  "Apply Filter" submit button, plus Bootstrap cards in a `row g-4` grid. The page
  data (stats, zones, citations, leader, members, updated-at) was already correct,
  but the presentation did not match the demo's light admin-console look (4
  stat cards, compact attached search + Filters popover with instant behavior,
  3-column team cards with a pinned footer and blue Team Lead strip).
- Location: `/teams` (whole page)

### Root Cause `(required)`

- The page was built before the HTML/CSS demo existed; its layout/theme matched
  the rest of the app's default components rather than the demo design.

### Files Changed `(required)`

- `resources/views/teams/index.blade.php`
- `documents/TeamsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/teams/index.blade.php`

Rewritten page content (app sidebar/topbar unchanged):

**Before:**
- `d-flex` header row with description + `btn btn-primary` Create button.
- `stat-card-sm` flex strip (Total / Active / Enforcers / Citations) using
  default muted styling.
- A GET `<form>` with `.filter-bar`: text input, native `status` `<select>`,
  "Has zones" `<input type="checkbox">`, `Filter` submit button, `Clear` link.
- `row g-4` with `col-md-6 col-xl-4` Bootstrap cards (avatar palette, amber
  lead card, gray "No lead assigned yet", avatar stack, footer metrics,
  Edit/Pause/Delete forms) and a dashed `dashed-create-card`.

**After:**
- Scoped `.tm-*` classes in a pushed `<style>` block; light theme retained.
- `.tm-top`: kicker + `tm-title` + description + blue `.tm-create` button.
- `.tm-stats`: 4 white stat cards (`tm-stat-card`) with big numbers
  (citations + active green, total + members blue), uppercase labels;
  responsive 4 → 2 → 1.
- `.tm-controls`: attached search input (own GET form) + `.tm-filters`
  wrapper with a `.tm-filter-trigger` (blue `is-active` with `×` clear when a
  filter is set) and a `.tm-popover`:
  - **Status** `.tm-select` row → `.tm-menu` of **instant GET links**
    (All Statuses / Active / Inactive), selected option shows a check; keeps
    `search` and `has_zones` params. *No "Apply Filter" button* (server-side GET
    applies immediately).
  - **Has zones** `.tm-check-row` → single GET link toggling `has_zones=1`
    (checked state shown when active); demo's checkbox behavior as a link.
- `.tm-grid`: 3-column responsive grid of `.tm-card` team cards (min-height
  390px, flex column, pinned footer):
  - Gray round avatar with initial, name + 2-line clamped description,
    green/gray status pill.
  - Blue `.tm-lead` strip (with leader initials + label + name + email) when a
    leader exists, or amber `.tm-warning` "No lead assigned yet".
  - Member avatar stack + "N members" line.
  - `.tm-card-footer`: metadata row (zones, citations/mo, updated-at with
    icons) and actions — outlined **Edit** link, circular Pause/Play button,
    circular Delete button. Real Bootstrap icons (`bi-pencil`, `bi-pause-fill`/
    `bi-play-fill`, `bi-trash`) inside the existing real forms/routes; replaces
    the demo's broken glyphs (`♢ Ⅱ ♲`).
- `.tm-create-card`: dashed "Create another team" card; `.tm-empty` spans the
  grid for the no-teams / no-filters-match state (existing copy kept).
- Inline `@push('scripts')` popover JS: trigger toggle, dropdown toggle,
  outside-click close, `Escape` close (IDs use `tm-` prefix).

**Filter param handling** (in-blade `@php`, mirrors `zones/index.blade.php`):
- `$searchOnly` = current `search` (empty values dropped).
- `$hasZonesOn` = `request()->boolean('has_zones')`.
- `$statusBase` = `search` + current `has_zones` (carried through status links).
- `$statusOptions` = GET links preserving the above; `status=null` clears.
- `$zonesToggleHref` = adds `has_zones=1` when off, drops it when on.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Overall look | Default boxed cards, mixed controls | Demo admin-console: 4 stat cards, compact filter row, 3-col grid |
| Filter by status | Native `<select>` + "Filter" submit | Filters popover → Status row → instant GET link (page reloads on click) |
| "Has zones" filter | Checkbox in the GET form | Filters popover → check-row link toggling `has_zones=1` |
| Applied filter feedback | Submit button always visible | Trigger turns solid blue + `×` clear link when any filter is active |
| Team cards | Bootstrap cards with mixed heights | Fixed-height cards, pinned footer, blue Team Lead strip / amber warning |
| Card actions | Edit + pause/play + delete forms | Same forms, restyled: outlined Edit, circular Pause/Play, circular Delete (Bootstrap icons) |
| "Create another team" | Dashed card (kept) | Restyled dashed card matching demo |
| No teams / no matches | Bootstrap empty-state card | Full-width `.tm-empty` panel in grid |

### Impact & Risk `(required)`

- Affects: `/teams` index page only.
- Risk: low — no controller, routes, models, or JS builds changed; filtering
  still happens server-side via the same GET params (`search`, `status`,
  `has_zones`). The `@php` block rebuilds query-string links from current
  `request()` values, so `has_zones` is preserved across Status changes and the
  toggle clears correctly. Dark/light theming unaffected (page stays light).
- The demo's "Apply Filter" button and toast were intentionally skipped (instant
  GET behavior chosen by user; the app shows its own session flash messages).

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `app/Http/Controllers/TeamController.php` (query/filter/stats logic)
- Routes and models (`users`, `zones`, `citations`, `team_user`)
- Edit/Create views and forms
- App layout sidebar/topbar and theme

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; a hard reload (Ctrl+F5) is needed to see the fix.
- The "Has zones" filter is a link toggle rather than the demo's checkbox; the
  visible checked state matches the demo's `.check` styling.
- Filters apply instantly via page reload (GET), not client-side hiding as in
  the static demo.

### Testing / Verification `(required)`

- `php -l resources/views/teams/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/teams`: popover opens/closes (trigger, outside click,
  `Escape`); Status options reload with `search` + `has_zones` preserved;
  Has-zones toggle toggles the link and shows the checked state; active trigger
  turns blue with `×` clear; stats render; cards show lead strip or amber
  warning, member stack, footer metadata; Edit/Pause/Delete work; empty and
  no-matches states render; responsive 4→2→1 stats and 3→2→1 grid.