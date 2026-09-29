# Zones Change Log

Change documentation for the Zone Management system. Follow the template in
`documents/documentation_Reference.md`. Append new entries at the end, newest last.

---

## Apply Zone Management Filter Demo Design to /zones

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-13

### Type of Change `(required)`

- Feature (UI redesign)

### Requested By / Source `(optional)`

- Requested by user; reference: `testPages/Zone_Management_Filter_Demo.html`
- Applied to: `http://127.0.0.1:8000/zones`

### Problem `(required)`

- The `/zones` page used a spread-out filter bar: a separate search input, a
  native team `<select>`, and a row of quick-filter chips (All / Active /
  Inactive / Assigned / Unassigned). This did not match the compact "Filters"
  popover design shown in the demo.
- Location: `/zones` → Filters area (search + team select + status chips)

### Root Cause `(required)`

- The page was built before the demo prototype existed; the filter UI was
  implemented as three separate controls instead of a single popover.

### Files Changed `(required)`

- `resources/views/zones/index.blade.php`

### What Parts Changed `(required)`

#### `resources/views/zones/index.blade.php`

**Before:**
- `.filter-bar` with a GET form containing a search input, a `team_id` `<select>` with `onchange="this.form.submit()"`, and a clear (`×`) link.
- A separate `.btn-group` of quick-filter chips for status/assignment.

**After:**
- Removed the native team `<select>` and the quick-filter chip group.
- Kept the search input in its own GET form (behavior unchanged).
- Added a `.filters-wrap` containing a **Filters trigger button** (`.filter-trigger`) that toggles a `.filters-popover`.
- The popover has two dropdown rows:
  - **All Teams** (`#team-filter-row`) → options: "All Teams" + each team, rendered as GET links with `team_id` merged into current params (`$chipBase`).
  - **Zone Status** (`#status-filter-row`) → options All / Active / Inactive / Assigned / Unassigned, mapped to `status`/`assignment` params.
- The trigger turns solid blue (`is-active`) when any filter is active and shows a `×` clear link.
- Added inline CSS (`.filters-wrap`, `.filter-trigger`, `.filters-popover`, `.filter-row`, `.filter-dropdown`, `.filter-option`, `.is-selected`, `.filters-divider`, plus `@media (max-width: 575.98px)` full-width popover).
- Added an inline toggle JS block (in `@push('scripts')`, outside the `@if ($zones->isNotEmpty())` guard) replicating the demo behavior: click trigger toggles popover; clicking a row toggles its dropdown and closes the other; outside click and `Escape` close everything.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Space taken by filters | Full row: search + select + 5 chips | Compact row: search + one "Filters" trigger |
| Filter by team | Native `<select>` dropdown | "Filters" popover → "All Teams" row → options list |
| Filter by status/assignment | Five quick chips inline | "Filters" popover → "Zone Status" row → options list |
| Page reload / sorting | GET params via select/chip links | GET params via popover option links (same params: `search`, `team_id`, `status`, `assignment`) |
| Clear filters | Clear `×` link next to select | Clear `×` inside the active "Filters" trigger button |
| Popover behavior | N/A | Toggle, outside-click and `Escape` close; dropdowns close each other |

### Impact & Risk `(required)`

- Affects: `/zones` only (Zone Management list + filters).
- Risk: no controller, route, model, or `zone-picker.js` changes; filtering
  still happens server-side via the same GET params, so map/list rendering is
  unaffected. Low regression risk.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `app/Http/Controllers/ZoneController.php` (filter query logic)
- `resources/js/zone-picker.js` (map/list rendering, highlight/fly-to behavior)
- Stat cards overview, zone list, map, and zone actions

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; a hard reload (Ctrl+F5) is needed to see the fix.
- Search input was intentionally kept (existing functionality not present in the
  demo). The popover covers Teams + Status only.

### Testing / Verification `(required)`

- `php -l resources/views/zones/index.blade.php`
- `php artisan view:cache`
- `npm run build`
- Manual QA on `/zones`: open/close popover (button click, outside click,
  `Escape`); pick a team → list/map update; pick status/assignment → list/map
  update; active trigger state + clear `×`; mobile width popover fits.

---

## Redesign /zones to match Zone_Management_HTML_CSS_Demo.html (Dark Console)

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-13

### Type of Change `(required)`

- Feature (UI redesign)

### Requested By / Source `(optional)`

- Requested by user; reference: `testPages/Zone_Management_HTML_CSS_Demo.html`
- Applied to: `http://127.0.0.1:8000/zones`

### Problem `(required)`

- The `/zones` page used the app's default light layout: padded card rows with
  a top row of stat cards, a filter bar, then a map card next to a plain zone
  list. This did not match the demo's dark "command center" screen (map filling
  the main area, right-hand panel with stats + zone cards + tools).
- Location: `/zones` (whole page)

### Root Cause `(required)`

- The page was built before the HTML/CSS demo existed; its layout/theme matched
  the rest of the app rather than the demo's dark console design.

### Files Changed `(required)`

- `resources/views/zones/index.blade.php`
- `resources/js/zone-picker.js`
- `documents/ZonesChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/zones/index.blade.php`

Rewritten page content:

**Before:**
- Light-theme rows: header + 4 clickable stat cards (Total/Assigned/Unassigned/
  Teams), filter bar (search + Filters popover + count badge), then a
  `row g-4` with map card (`col-lg-7`) and flat list card (`col-lg-5`).

**After:**
- Scoped dark theme: `main.page-bg` overridden to navy `#081b2c`, zero padding,
  flex column (page-specific via `@push('styles')` + `!important`).
- Full-height `.zone-console` grid: `minmax(0,1fr) 303px` = **map pane** (left,
  fills area, MapLibre map + empty state) and **right panel** (`.zone-panel`).
- Right panel contains:
  - Head: "Zones" title + description + dark-blue **Create Zone** button.
  - **Zone Status box**: 3 stats (Assigned / Teams / Unassigned) as clickable
    links to the existing `assignment` filter URLs (replaces the old top cards).
  - Tools row: dark search input attached to a **Filters ▼** button (replaces
    the old funnel-trigger look; same popover logic/IDs/JS).
  - Count line "N zones".
  - **Zone cards** list (scrolling): name + description, Active(green)/Inactive
    (gray) pill, team pill (brand color) or red **Unassigned** pill, radius, and
    3 compact action buttons (pencil / pause-fill·play-fill / trash) hitting the
    real `zones.edit`, `zones.toggle-active`, `zones.destroy` routes. Cards keep
    `data-zone-id` and the click-to-fly-to-map highlight.
  - **Filters popover** as two boxed `.select` dropdowns (demo styling) with
    `.menu` options; All Teams + real teams, and Zone Status
    All/Active/Inactive/Assigned/Unassigned; server-side GET links; selected
    item shows check.
- Responsive: panel width 285px ≤1199.98px; below 991.98px the grid stacks
  (map min 500px/60vh, panel below, popover fixed top-right).

#### `resources/js/zone-picker.js`

- `createDetailOverlay(containerId, actions = null)`: when `actions` is
  provided, appends an "Actions" divider + 3 buttons to the map detail overlay:
  - **Edit** → link `actions.editUrl(id)` (bootstrap `bi-pencil`).
  - **Activate/Deactivate** → POST form to `actions.toggleUrl(id)` with
    `_method=PATCH` and CSRF token; icon toggles `bi-pause-fill`/`bi-play-fill`
    from `zone.is_active`.
  - **Delete** → POST form to `actions.deleteUrl(id)` with `_method=DELETE`,
    `confirm()` dialog, `bi-trash`.
  - Added supporting CSS (`.mdo-divider`, `.mdo-actions-label`, `.mdo-actions`,
    `.mdo-form`, `.mdo-btn`, `.mdo-btn.delete`).
- `initZoneViewer(containerId, options)`: accepts `detailActions` option and
  forwards it to `createDetailOverlay`. Pages that do not pass it (dashboard,
  teams picker, zone editor) are unchanged.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Overall look | Light theme, padded card rows | Dark navy console; map fills main area, panel on right |
| Zone stats | 4 top cards (incl. Total) | 3-stat "Zone Status" box in right panel (Assigned/Teams/Unassigned), still clickable filters |
| Zone list | Flat list items in a side card | Card-style rows in right panel with pills + compact action buttons |
| Map area | `col-lg-7` card, not full height | Full-height map pane |
| Map detail card | Info only (status/team/radius/area/coords + close) | Same info **plus** Edit / Activate-Deactivate / Delete buttons wired to real routes |
| Search + filters | Separate search box and rounded funnel button | Attached search input + "Filters ▼" button (demo style); popover now boxed `.select`/`.menu` |
| Responsive ≥992px | Standard stacked/multicol Bootstrap | Map + 303px (285px) panel side by side; <992 stacks panel under map |

### Impact & Risk `(required)`

- Affects: `/zones` list/management page only.
- Risk:
  - `zone-picker.js` is shared; the detail overlay markup/actions only render
    when a caller passes `detailActions`, so dashboard/teams/editor maps keep
    the old info-only overlay. Verify those pages after `npm run build`.
  - Page padding/background is overridden with `!important`, scoped via the
    page's own pushed `<style>`; safe because the style only exists on `/zones`.
  - Old top stat cards (Total/Assigned/Unassigned/Teams) removed; filtering via
    the Assigned/Unassigned/Teams stats is preserved as links. "Total Zones" is
    now only visible as the count line.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `app/Http/Controllers/ZoneController.php` (filter query logic, stats)
- Routes and models
- Search/filter param names and server-side GET behavior
- The interactive MapLibre map, markers, circle outlines, and fly-to behavior
- App layout sidebar/topbar (light theme kept, per scope decision)

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS/JS; a hard reload (Ctrl+F5) is needed to see the
  change.
- The demo's sample map is a static mock; the live page keeps the real
  interactive map, so the center area will not reproduce the demo's fake
  terrain.
- The demo's action symbols (`♢ Ⅱ ♲`) were broken placeholders; replaced with
  real Bootstrap icons and functional forms.

### Testing / Verification `(required)`

- `php -l resources/views/zones/index.blade.php`
- `node --check resources/js/zone-picker.js`
- `npm run build` (asset rebuilt)
- `php artisan view:cache` / `view:clear`
- Manual QA on `/zones`: console layout fills viewport; zone card → map fly-to +
  highlight; marker click → detail card with working Edit/Pause/Delete; zone
  status stats filter correctly; search + Filters popover (teams + status)
  filter server-side; empty/filtered states render; responsive stacking below
  992px.

---

## Fix: map detail overlay "permanently closed" until page reload

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-13

### Type of Change `(required)`

- Bug fix (UI / JavaScript)

### Requested By / Source `(optional)`

- Reported by user on `/zones`: after pressing the detail overlay's close (`×`)
  button, clicking a zone card again did not reopen the overlay until the page
  was reloaded.

### Problem `(required)`

- The `.map-detail-overlay` closed via `×` appeared to stay hidden for the rest
  of the session; zone-card / marker clicks no longer brought it back.
- Location: `resources/js/zone-picker.js` → `createDetailOverlay` (overlay
  show/hide) and the marker / map `click` listeners in `initZoneViewer`.

### Root Cause `(required)`

- The overlay was purely class-toggled (`is-hidden`) with fragile wiring:
  1. The `×` handler used `this.hide()`; if `show` ever ran outside the
     `detailOverlay.show()` method context, `this` resolved wrong and the overlay
     could never be re-opened.
  2. `map.on('click')` hides the overlay whenever `queryRenderedFeatures` is
     empty. If a marker click also produced a map click right after `show()`, the
     overlay opened and was immediately hidden again → looked permanently closed.
  3. `show()` rebuilt `innerHTML` and, only then, removed `is-hidden`; any render
     hiccup alternated (or left it) in the hidden state with no recovery path.

### Files Changed `(required)`

- `resources/js/zone-picker.js`
- `documents/ZonesChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/js/zone-picker.js` (`createDetailOverlay`)

**Before:**
- Removed a pre-existing `.map-detail-overlay` in the container if present.
- Handled open/close through one `show()`/`hide()` pair using `this.hide()` in
  the `×` listener; `show()` rendered `innerHTML` first, then removed
  `is-hidden`.

**After:**
- `createDetailOverlay` is idempotent: any existing `.map-detail-overlay` in the
  container is removed first (prevents stacked duplicate overlays on re-init).
- Returned object is a named `overlay` (`{ el, show, hide }`); the `×` listener
  calls `hide()` directly (no `this` binding).
- `show()` now:
  - Reveals **first** (`is-hidden` removed and leftover inline `opacity` /
    `pointer-events` cleared) BEFORE rebuilding markup.
  - Builds markup inside `render()`, wrapped in `try/catch` (logs the error and
    shows a minimal fallback card) so a render error can never strand it hidden.
  - Records `overlay._lastShownAt = Date.now()`.
  - `×` listener also calls `e.stopImmediatePropagation()`.
- `hide()` adds `is-hidden` **and** inline fallbacks (`opacity:0`,
  `pointer-events:none`).

#### `resources/js/zone-picker.js` (marker / map click listeners)

- Marker `click` listeners in `initTeamZonePicker`, `initZoneEditor`, and
  `initZoneViewer` now call `e.stopPropagation()` + `e.stopImmediatePropagation()`
  so a marker click cannot bubble into a map click.
- Map `click` hide handlers (empty-area click, team picker + zone viewer) skip
  hiding when `overlay._lastShownAt` is within the last 250 ms — an immediate
  follow-on map click can no longer close an overlay that was just shown.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Click marker → detail overlay | Shows | Shows (unchanged) |
| Press `×` on overlay | Hides via `this.hide()` | Hides via closure `hide()` (no `this`) |
| Click same zone card/marker again | Overlay could stay hidden until reload | Overlay reliably reopens |
| Click empty map area | Hides overlay | Hides overlay (unchanged); a map click right after a marker click is ignored for 250 ms |
| Multiple overlay inits | Possible duplicates | Pre-existing overlay removed first |
| Dashboard/teams/editor overlays | Info-only overlay | Unchanged (same hardened show/hide, still no action buttons) |

### Impact & Risk `(required)`

- Affects: the shared map detail overlay used by `/zones`, dashboard,
  teams-zone picker, and the zone editor.
- Risk: low. Behavior change is hardening only — show is performed before
  render, and only a 250 ms debounce was added to the empty-map-click hide.
  Built asset rebuilt; release a new build for the change to take effect.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Blade views, controllers, routes, models
- `detailActions` (Edit/Pause/Delete forms) wiring
- Overlay markup / styling

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old JS; a hard reload (Ctrl+F5) is needed to see the fix.
- If the overlay ever fails to reopen again, reproduce in DevTools: check the
  console for "Map detail overlay render failed" and confirm whether a map
  `click` fires after the marker click.

### Testing / Verification `(required)`

- `node --check resources/js/zone-picker.js`
- `npm run build` (asset rebuilt: `zone-picker-DJwSm48K.js`)
- Manual QA on `/zones`: marker → overlay shows → `×` → hides → same zone card
  again → overlay shows (repeat several times); empty-map click still hides;
  Edit/Pause/Delete buttons still work; dashboard map still shows info-only
  overlay.

---

## Fix: Create Zone button in the right panel ran off-screen

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-13

### Type of Change `(required)`

- Bug fix (CSS / layout)

### Requested By / Source `(optional)`

- Reported by user on `/zones`: the `.zone-create-btn` (Create Zone) appeared
  off-screen / clipped.

### Problem `(required)`

- In the right panel header, the **Create Zone** button (.zone-create-btn) was
  pushed out of view on the right and clipped.
- Location: `/zones` → right panel → `.zone-panel-head`

### Root Cause `(required)`

- `.zone-panel-head` is a flex row (`justify-content: space-between`). Its left
  text `<div>` had no `min-width: 0`, and `.zone-panel-desc` used
  `white-space: nowrap`. The nowrap description ("Define patrol zones and assign
  them to response teams.") fixes the text div wider than the panel content
  width (303px panel − 36px padding − 12px gap ≈ 255px vs ~230px text + ~90px
  button), leaving no room for the button. Because `.zone-panel { overflow:
  hidden }`, the `flex-shrink: 0` button was clipped off-screen to the right.

### Files Changed `(required)`

- `resources/views/zones/index.blade.php`
- `documents/ZonesChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/zones/index.blade.php`

**Before:**

```css
.zone-panel-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.zone-panel-desc { font-size: 0.68rem; color: #8297aa; margin-top: 4px; white-space: nowrap; }
```

**After:**

```css
.zone-panel-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.zone-panel-head > div { min-width: 0; }
.zone-panel-desc { font-size: 0.68rem; color: #8297aa; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
```

- Added `.zone-panel-head > div { min-width: 0; }` so the text wrapper can
  shrink within the flex row.
- Added `overflow: hidden; text-overflow: ellipsis;` to `.zone-panel-desc` so
  the nowrap description truncates instead of forcing the container wider.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Long description in panel head | Pushes Create Zone off-screen (clipped by overflow:hidden) | Description truncates with `…`; button stays fully visible |
| Panel width 303px / 285px | Button clipped | Button fully clickable |

### Impact & Risk `(required)`

- Affects: `/zones` right panel header only.
- Risk: minimal — pure CSS; no markup or data changes. The heading description
  may show an ellipsis when the panel is narrow, by design.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Markup, controllers, routes; the two horizontal "Clear filters" buttons
  (empty-map state, zone list empty state) are centered and unchanged.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; a hard reload (Ctrl+F5) is needed to see the fix.

### Testing / Verification `(required)`

- `php -l resources/views/zones/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/zones`: Create Zone button visible and clickable at both 303px
  and 285px panel widths; description shows ellipsis instead of overflowing;
  page still responsive below 992px.

---

## Reorganize zone panel header into upper/lower rows

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-13

### Type of Change `(required)`

- Refactor / UI tweak (layout)

### Requested By / Source `(optional)`

- Requested by user on `/zones`: the panel description text was being cut off
  (`text-overflow: ellipsis`), so the header was restructured to put the
  description on its own line.

### Problem `(required)`

- The `.zone-panel-head` held the title + description in one flex text `<div>`
  next to the Create Zone button. Because the panel is narrow (~303px/285px),
  the description was clipped with an ellipsis even after the previous
  `min-width: 0` fix.
- Location: `/zones` → right panel → `.zone-panel-head`

### Root Cause `(required)`

- The description shared the header row with the title and the Create Zone
  button, leaving too little horizontal room; single-line truncation still cut
  the text.

### Files Changed `(required)`

- `resources/views/zones/index.blade.php`
- `documents/ZonesChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/zones/index.blade.php`

**Before:**

```html
<div class="zone-panel-head">
    <div>
        <div class="zone-panel-title">Zones</div>
        <div class="zone-panel-desc">Define patrol zones and assign them to response teams.</div>
    </div>
    <a href="{{ route('zones.create') }}" class="zone-create-btn">Create Zone<i class="bi bi-plus-lg"></i></a>
</div>
```

**After:**

```html
<div class="zone-panel-head">
    <div class="upper-header">
        <div class="zone-panel-title">Zones</div>
        <a href="{{ route('zones.create') }}" class="zone-create-btn">Create Zone<i class="bi bi-plus-lg"></i></a>
    </div>
    <div class="lower-header">
        <div class="zone-panel-desc">Define patrol zones and assign them to response teams.</div>
    </div>
</div>
```

CSS changes:
- `.zone-panel-head` is now a flex column (`flex-direction: column`) stacking
  the two rows.
- New `.upper-header`: flex row, `justify-content: space-between`, centered,
  holding title + Create Zone button.
- New `.lower-header`: `margin-top: 4px` — full-width description row.
- `.zone-panel-desc`: removed `white-space: nowrap; overflow: hidden;
  text-overflow: ellipsis;` so the text wraps naturally on its own line.
- Removed the now-unused `.zone-panel-head > div { min-width: 0; }` rule.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Header layout | Title + description + button in one row | "Zones" + Create Zone in the top row; full-width description below |
| Long description | Clipped with ellipsis | Wraps onto its own line, fully readable |
| Create Zone button | Visible after previous fix | Still fully visible/clickable at 303px/285px |

### Impact & Risk `(required)`

- Affects: `/zones` right panel header only.
- Risk: minimal — markup + scoped CSS only; no data, route, or JS changes. The
  panel now starts slightly taller (two header rows), which only shifts the
  content below it.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Zone status box, tools row, zone cards, map pane, controllers, routes

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; a hard reload (Ctrl+F5) is needed to see the fix.

### Testing / Verification `(required)`

- `php -l resources/views/zones/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/zones`: header shows title + Create Zone on row one and the
  full description on row two with no clipping; Create Zone button clickable at
  303px/285px; stack layout below 992px.

---

## Unassigned stat count color-coded by thresholds

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-13

### Type of Change `(required)`

- Feature (visual indicator)

### Requested By / Source `(optional)`

- Requested by user on `/zones`: the Unassigned count in the Zone Status box
  should be color-coded by its value.

### Problem `(required)`

- The **Unassigned** stat number in the Zone Status box was always colored red
  (`.zone-stat-num.unassigned { color: #ed4448 }`), with no indication of how
  urgent the workload was.
- Location: `/zones` → right panel → Zone Status box → Unassigned number

### Root Cause `(required)`

- The stat used a single semantic class that hard-coded one red color
  regardless of the count.

### Files Changed `(required)`

- `resources/views/zones/index.blade.php`
- `documents/ZonesChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/zones/index.blade.php`

**Before:**

```css
.zone-stat-num.unassigned { color: #ed4448; }
```

**After:**

```css
.zone-stat-num.level-ok     { color: #13a956; }  /* green 0–2 */
.zone-stat-num.level-warn   { color: #f2b63f; }  /* amber 3–5 */
.zone-stat-num.level-danger { color: #ed4448; }  /* red 6+ */
```

In-blade `@php` prep (after the existing `$statusLabel` block):

```php
$unassignedCount = (int) $stats['unassigned'];
$unassignedLevel = $unassignedCount <= 2 ? 'level-ok' : ($unassignedCount <= 5 ? 'level-warn' : 'level-danger');
```

Stat markup (`zone-stat-num`):

```html
<div class="zone-stat-num unassigned {{ $unassignedLevel }}">{{ $stats['unassigned'] }}</div>
```

The `unassigned` class stays as a semantic hook; color is now driven by the
level class. Colors match the existing dark-console palette (green `#13a956`,
amber `#f2b63f`, red `#ed4448`).

### Behavior of the New Changes `(required)`

| Unassigned count | Before | After |
|---|---|---|
| 0–2 | Red | Green |
| 3–5 | Red | Amber |
| 6+ | Red | Red |

### Impact & Risk `(required)`

- Affects: `/zones` Zone Status box → Unassigned number only.
- Risk: minimal — inline Blade + scoped CSS; no controller or data changes
  (`$stats['unassigned']` is still `Zone::whereNull('team_id')->count()`). The
  per-zone-card "Unassigned" pill and the filter dropdown label are unchanged.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `app/Http/Controllers/ZoneController.php` (stats logic)
- Zone card "Unassigned" badge (`zone-team-badge.unassigned`), dashboard, teams

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; a hard reload (Ctrl+F5) is needed to see the fix.
- To eyeball all three colors, the zone table must have a count that falls in
  each range (or temporarily adjust the thresholds).

### Testing / Verification `(required)`

- `php -l resources/views/zones/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/zones`: Unassigned number is green at 0–2, amber at 3–5, red
  at 6+; Assigned/Teams numbers still blue; the stat link still filters to
  unassigned zones.