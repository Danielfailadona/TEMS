# Audit Logs Change Log

Change documentation for the Audit Logs system. Follow the template in
`documents/documentation_Reference.md`. Append new entries at the end, newest last.

---

## Apply Audit Logs HTML/CSS Demo Design to /audit-logs

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-13

### Type of Change `(required)`

- Feature (UI redesign)

### Requested By / Source `(optional)`

- Requested by user; reference: `testPages/Audit_Logs_HTML_CSS_Demo.html`
- Applied to: `http://127.0.0.1:8000/audit-logs`

### Problem `(required)`

- The `/audit-logs` page used the app's default layout: a plain count line, a
  standard Bootstrap GET form (row of selects/date inputs/search/clear), and a
  default Bootstrap table. The page data (activities, log names, events,
  pagination) was already correct but the presentation did not match the demo's
  light admin-console look (toolbar card with a Filters overlay + attached
  search + removable filter chips, "Showing X of Y entries", fixed-column table
  with colored event pills).
- Location: `/audit-logs` (whole page)

### Root Cause `(required)`

- The page was built before the HTML/CSS demo existed; it used default
  Bootstrap components rather than the demo's toolbar/table design. The demo's
  filtering is client-side JS, which conflicts with server-side pagination, so
  the port keeps server-side GET filtering.

### Files Changed `(required)`

- `resources/views/audit-logs/index.blade.php`
- `documents/AuditLogsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/audit-logs/index.blade.php`

Rewritten page content (app sidebar/topbar unchanged).

**Before:**
- `span.text-muted` "N total entries".
- A `card` with a `row g-2` GET form: `log_name` select, `event` select,
  `date_from`/`date_to` date inputs, search input + magnifier submit + Clear
  link.
- A second `card` with a default `table table-hover` (6 columns incl. the
  properties eye + collapsible JSON row under each entry), Bootstrap event
  badges (`bg-success`/`bg-warning`/`bg-danger`/`bg-secondary`), and pagination
  in `card-footer`.

**After:**
- Scoped `.al-*` classes in a pushed `<style>` block; light theme retained.
- `.al-top`: kicker "Operations console" + "Audit Logs" title.
- `.al-toolbar`: white toolbar card. Inside:
  - `.al-toolbar-row` (a `<form method="GET">`):
    - `.al-filter-wrap` with a **Filters ▾ trigger** (`.al-filter-btn`,
      keyboard accessible) that toggles the `.al-overlay` panel:
      - **Logs:** native `<select name="log_name">` styled as the demo's
        `.select-like` (`appearance:none` + chevron background image).
      - **Events:** native `<select name="event">` styled the same way.
      - **Date:** native `<input type="date">` From/To pair (browser-rendered
        date format) separated by a dash.
      - **Apply Filters** submit button — submits all filters as one GET.
    - Attached `.al-search-wrap` (search input + magnifier submit button).
  - `.al-chips` (below the row, **not inside the form**): one removable chip per
    active filter (`Log`, `Event`, `From`, `To`, `Search`). Each chip is an
    `<a>` GET link to `route('audit-logs.index', request()->except(param))` that
    clears only that filter while preserving the others. Chips render only when
    at least one filter is active.
- `.al-table-card`: white table card.
  - `.al-entry-count`: "Showing **[page count]** of **[total]** entries".
  - `.al-table-wrap` + `.al-table` (6 columns, fixed widths
    12/16/10/34/18/10%). Time styled dark-bold, Subject muted.
  - Event as colored `.al-pill` (shape from demo, colors kept from live):
    `created` green, `updated` amber, `deleted` red, other gray; label uses
    `ucfirst($activity->event)`.
  - Properties eye button (`.al-eye-btn`) kept — toggles the collapsible
    JSON `<pre>` row (live feature the demo lacks).
  - Empty state row: "No audit log entries match your search." centered.
  - Pagination kept via `$activities->links()` in a bordered footer.
- Inline `@push('scripts')` JS: filter overlay open/close, outside-click close,
  `Escape` close, Enter/Space keyboard support. Dropdowns/date inputs need no
  JS (native elements + form submit).

**Filter params (unchanged):** `log_name`, `event`, `date_from`, `date_to`,
`search`. Controller behavior untouched.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Overall look | Default Bootstrap cards + form row | Toolbar card + filter overlay + chips + fixed-column table card |
| Filter by Logs/Events | Inline native selects in a form row | Filters ▾ overlay → styled native selects → Apply Filters submit |
| Date range | Inline date inputs + Filter button | Filters ▾ overlay → From/To date inputs (native format) → Apply |
| Search | Attached to the form row | Attached search bar next to Filters (same GET submit) |
| Active filters visible | Text input values + Clear link | Removable chips under the toolbar (click `×` clears that one filter) |
| Entry count | "N total entries" text | "Showing X of Y entries" |
| Event display | Bootstrap badge (`bg-success` etc.) | Rounded `.al-pill` (same green/amber/red/gray semantics) |
| Properties JSON | Eye + collapsible row | Kept, restyled (`.al-eye-btn` + `.al-properties pre`) |
| Pagination | `card-footer` | Bordered `.al-pagination` footer (kept) |
| Responsive | Bootstrap table-responsive | `.al-table-wrap` scroll + stacked toolbar below 900px |

### Impact & Risk `(required)`

- Affects: `/audit-logs` page only (roles: super_admin, administrator).
- Risk: low — no controller, route, model, or JS build changes. Filtering
  remains server-side GET with the same params; pagination untouched. The
  properties eye/collapse relies on Bootstrap's global collapse JS already
  loaded by the app layout (it worked before, unchanged). Native `<select>` /
  `<input type="date">` elements keep browser-default behavior (localized date
  format shown as the user requested).

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `app/Http/Controllers/AuditLogController.php` (query/filter/pagination logic)
- Routes, Spatie Activitylog model, events log data
- App layout sidebar/topbar and theme

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; a hard reload (Ctrl+F5) is needed to see the fix.
- The demo filters logs/events client-side; this port filters server-side via
  GET (required to coexist with pagination), so a page reload occurs on Apply /
  search. The "Apply Filters" button was kept (user chose Plan A) instead of
  the zones/teams instant-get-link popover, because the date-range controls do
  not map to discrete links.
- The demo's blue (Approved) and purple (Status Update) pills have no matching
  events in this system; live event colors were kept per user request.

### Testing / Verification `(required)`

- `php -l resources/views/audit-logs/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/audit-logs`: overlay opens/closes (trigger click, outside
  click, `Escape`, Enter/Space); Logs/Events selects retain selection;
  From/To date inputs show the native date format and submit; Apply Filters
  submits all params; search submits; chips render for active filters and each
  `×` clears only that filter (URL reflects it); event pills colored
  correctly; eye rows expand/collapse; "Showing X of Y" count correct;
  pagination works; empty state shows when no matches; toolbar/table stack
  below 900px.

---

## Event Tags: Semantic Colors + [Icon][Text] Pills

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-15

### Type of Change `(required)`

- Feature (UI change)

### Requested By / Source `(optional)`

- Requested by user (`/audit-logs`); icons from `public/images/Icons/`

### Problem `(required)`

- Event rows logged without an `event` (approvals, status changes, force
  logouts from `UserController`) rendered as a **gray pill with no text**
  because `ucfirst(null)` produced an empty string. Also, the pill colors did
  not follow the requested semantic grouping.
- Location: `/audit-logs` Event column

### Root Cause `(required)`

- Previous pill logic only handled the `created`/`updated`/`deleted` event
  values (`event` null fell through to the gray `other` class with empty text).

### Files Changed `(required)`

- `resources/views/audit-logs/index.blade.php`
- `documents/AuditLogsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/audit-logs/index.blade.php`

**Before:** pill CSS classes `.created` (green), `.updated` (amber), `.deleted`
(red), `.other` (gray); Event cell rendered `ucfirst($activity->event)` -> empty
for null events.

**After:**

1. CSS: replaced `.al-pill.created/.updated/.deleted/.other` with semantic
   classes `.al-pill.success` (green `#22a85b`), `.al-pill.danger` (red
   `#ef4444`), `.al-pill.neutral` (gray `#6b7280`); added `.al-pill-icon`
   (16x16, `object-fit: contain`, 7px right margin).
2. Event cell: a per-row `@php` block computes `$pillClass`, `$pillText`,
   `$pillIcon`, then renders `<span class="al-pill $pillClass"><img
   class="al-pill-icon" src="images/Icons/...">$pillText</span>` — the
   **[icon][text]** arrangement requested.

| Condition | Icon (public/images/Icons/) | Text | Color |
|---|---|---|---|
| event `created` | Createed.png | Created | green (success) |
| event `updated` | Updated.png | Updated | gray (neutral) |
| event `deleted` | Deleted.png | Deleted | red (danger) |
| description contains `rejected` | Rejected User.png | Rejected | red |
| description contains `approved user` | Approved User.png | Approved | green |
| description contains `suspended` | Status Suspended.png | Suspended | red |
| description contains `approved` | Status Approved.png | Status Approved | green |
| description contains `force logged out` | Forced Logged out.png | Logout | red |
| fallback | none | `ucfirst(event) ?: 'Action'` | gray |

Semantic grouping per request: termination/rejection/destruction = **red**
(deleted, rejected, suspended, logout); approval/created/welcoming = **green**
(created, approved, status approved); editing/updating/non-urgent change =
**gray/neutral** (updated, fallback).

### Behavior of the New Changes `(required)`

| Row type | Before | After |
|---|---|---|
| `Approved user X` | gray empty pill | green `[Approved User icon] Approved` |
| `Changed ... status to approved` | gray empty pill | green `[Status Approved icon] Status Approved` |
| `Changed ... status to suspended` | gray empty pill | red `[Status Suspended icon] Suspended` |
| `Force logged out ...` | gray empty pill | red `[Forced Logged out icon] Logout` |
| `created` | green `Created` | green `[Createed icon] Created` |
| `updated` | amber `Updated` | gray `[Updated icon] Updated` |
| `deleted` | red `Deleted` | red `[Deleted icon] Deleted` |
| any future unknown event | gray text only | gray, icon only if mapped |

### Impact & Risk `(required)`

- Affects: `/audit-logs` page only (super_admin, administrator).
- Risk: low — view-only; controller, route, filters, pagination unchanged.
- Icon files referenced by path in `public/images/Icons/`; removing/renaming a
  file breaks that tag's icon (falls back to colored pill, no image).
  `.al-pill-icon` images keep their own colors.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Controller/route/filter params, chips, entry count, table columns, collapse
  rows, pagination, app layout/theme.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; hard reload (Ctrl+F5) to see the change.
- Logout was assigned red per user decision (security-style action), even
  though it is not strictly a termination.
- Icons are 55x55 originals rendered at 16px inside the pill; if any icon
  looks off, adjust `.al-pill-icon` size.

### Testing / Verification `(required)`

- `php -l resources/views/audit-logs/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/audit-logs`: every row type shows its icon+text+color per the
  table above; pills align (icon then text, centered); no empty/gray-only
  pills; filters/chips/pagination/eye rows still work.

---

## Event Tags: Frame_3 Styling (Logo Circle + Outlined Pill)

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-15

### Type of Change `(required)`

- Feature (UI restyle)

### Requested By / Source `(optional)`

- Requested by user (`/audit-logs`); visual reference:
  `testPages/Frame_3_Corrected_HTML_CSS_Demo.html`

### Problem `(required)`

- Event tags looked like generic filled pills with a loose 16px icon. Wanted:
  the Frame_3 demo's "logo in a circle + title text" pill arrangement.
- Location: `/audit-logs` Event column

### Root Cause `(required)`

- The pill had no inner circular icon container (the demo's `.logo-circle`)
  and lacked the outlined-frame border.

### Files Changed `(required)`

- `resources/views/audit-logs/index.blade.php`
- `documents/AuditLogsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/audit-logs/index.blade.php`

**Before:** `.al-pill` 28px tall, radius 14px, no border, plain image 16px left
of text.

**After** (demo scaled to table rows; filled colors kept per user decision):

1. `.al-pill`: height 32px, border-radius 16px, added
   `border: 1px solid rgba(255,255,255,.35)` (outlined-frame look on the
   filled color), padding `0 14px 0 5px`. Success/danger/neutral fills
   unchanged.
2. New `.al-pill-icon-circle`: 24px white circle (border-radius 50%, 1px white
   border) - the scaled `.logo-circle` from the demo - centered via
   `inline-flex`, 8px right margin, `flex-shrink: 0`.
3. `.al-pill-icon`: resized to 15px, `object-fit: contain`, centered inside the
   circle (its old `margin-right` removed - spacing now via the circle).
4. Markup: icon is now wrapped in the circle:
   `<span class="al-pill-icon-circle"><img ...></span>` then text - the
   `[logo-circle][text]` arrangement.

Mapping logic, semantic colors, controller, and other columns unchanged.

### Behavior of the New Changes `(required)`

| Row type | Before | After |
|---|---|---|
| All event tags | filled pill, plain 16px icon, then text | outlined filled pill, white circular icon badge (logo-circle), then text |
| Single | n/a | pill 32x(px) balance, icon centered in a 24px white ring |

### Impact & Risk `(required)`

- Affects: `/audit-logs` page only.
- Risk: low - view-only CSS + markup; no controller/route/data changes.
- Icon circles are always white regardless of pill color; icons keep their own
  colors inside the white badge.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Event mapping logic, semantic colors, chips, filters, pagination, collapse
  rows, other columns, layout/theme.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; hard reload (Ctrl+F5) to see the change.
- If a pill looks cramped, tune `.al-pill-icon-circle` size (24px) or
  `.al-pill` height (32px).

### Testing / Verification `(required)`

- `php -l resources/views/audit-logs/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/audit-logs`: each tag shows the white circular icon badge
  followed by the label, outlined filled pill, icons centered, text aligned;
  filters/chips/pagination/eye rows still work.

---

## Event Column: Full-Height Filled Logo + Plain Text; Eye Column 10% -> 7%

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-15

### Type of Change `(required)`

- Feature (UI change)

### Requested By / Source `(optional)`

- Requested by user (`/audit-logs`)

### Problem `(required)`

- The Event tag was a compact filled pill (icon circle 24px + text inside a
  32px pill). Wanted: the Event column split into two parts - a **filled logo**
  sized to the row height, with the **text outside** any pill. Also the eye
  column was too wide (10%).
- Location: `/audit-logs` Event and action columns

### Root Cause `(required)`

- N/A (styling request)

### Files Changed `(required)`

- `resources/views/audit-logs/index.blade.php`
- `documents/AuditLogsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/audit-logs/index.blade.php`

**Before:** Event cell held a `.al-pill` (32px, colored fill, dotted border)
whose icon sat in a small white 24px `.al-pill-icon-circle`; eye column width
10%; event column 10%.

**After:**

1. Column widths: Event `th/td:nth-child(3)` 10% -> **13%** (absorbs the 3%
   freed by the eye column); Eye `th/td:nth-child(6)` 10% -> **7%**.
2. Removed the `.al-pill`, `.al-pill-icon-circle`, `.al-pill-icon` CSS.
3. New `.al-logo`: 52x52px, border-radius 50%, 2px white border, centered with
   `inline-flex`; filled with the semantic color (`success`/`danger`/`neutral`
   = green/red/gray) - the **filled logo sized to the row height**. Icon inside
   at 32px (`object-fit: contain`).
4. New `.al-event-text`: plain dark text (`#435067`, 0.85rem, 600 weight, 10px
   margin-left) - **no pill** around the label.
5. Event `td:nth-child(3)` vertical padding set to 0 so the 52px logo exactly
   equals the row height (~52px); other cells keep their padding.
6. Markup: `<span class="al-logo $pillClass"><img ...></span><span
   class="al-event-text">$pillText</span>`; fallback rows with no icon render
   the text only (no empty circle).

Mapping logic, semantic colors, controller, other columns unchanged.

### Behavior of the New Changes `(required)`

| Aspect | Before | After |
|---|---|---|
| Event column width | 10% | 13% |
| Eye column width | 10% | 7% |
| Event cell | small pill, white 24px circle + text in pill | full-height filled color circle (52px = row height) + plain text |
| Text treatment | white text on pill | dark text, no pill background |

### Impact & Risk `(required)`

- Affects: `/audit-logs` page only.
- Risk: low - view-only CSS + markup; no controller/route/data changes.
- Icons render inside the filled colored circle keeping their original colors;
  if any clash, add `filter: invert(1)` to `.al-logo img` or switch the circle
  background to white.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Event mapping logic, semantic colors, chips, filters, pagination, collapse
  rows, other columns, layout/theme.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; hard reload (Ctrl+F5) to see the change.
- Rows without an icon render text-only (no empty circle).
- `.al-logo` is a fixed 52px square; if the row height changes elsewhere it
  would no longer match.

### Testing / Verification `(required)`

- `php -l resources/views/audit-logs/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/audit-logs`: Event column shows `[filled circle logo][text]`
  with circle height equal to the row height; eye column narrower; icons
  centered; fallback rows text-only; filters/chips/pagination/eye rows still
  work.

---

## Table Column Reorder: Event Column First

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-15

### Type of Change `(required)`

- Feature (UI change)

### Requested By / Source `(optional)`

- Requested by user (`/audit-logs`)

### Problem `(required)`

- Event column was third; user wanted Event first, everything else after it.
- Location: `/audit-logs` table

### Root Cause `(required)`

- N/A (styling request)

### Files Changed `(required)`

- `resources/views/audit-logs/index.blade.php`
- `documents/AuditLogsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/audit-logs/index.blade.php`

**Before column order:** Time, User, Event, Description, Subject, (eye).

**After column order:** Event, Time, User, Description, Subject, (eye).

1. `<thead>`: reordered the `<th>` headers - Event first.
2. `<tbody>` row: moved the Event cell (`.al-logo` + `.al-event-text` + its
   `@php` mapping) to be the first `td`, followed by Time, User, Description,
   and Subject cells.
3. CSS `nth-child` width selectors updated to the new positions (Event still
   13%, Time 12%, User 16%, Description 34%, Subject 18%, eye 7%).
4. The zero-vertical-padding override for the Event cell moved from
   `td:nth-child(3)` to `td:nth-child(1)`.
5. Collapse/empty rows still use `colspan="6"` (unchanged).

### Behavior of the New Changes `(required)`

| Position | Before | After |
|---|---|---|
| Column 1 | Time | Event |
| Column 2 | User | Time |
| Column 3 | Event | User |
| Column 4 | Description | Description |
| Column 5 | Subject | Subject |
| Column 6 | (eye) | (eye) |

### Impact & Risk `(required)`

- Affects: `/audit-logs` page only.
- Risk: low - column order and CSS selectors only; no controller/route/data
  changes. Widths preserved per column.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Event mapping logic, semantic colors, chips, filters, pagination, collapse
  rows, layout/theme.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; hard reload (Ctrl+F5) to see the change.

### Testing / Verification `(required)`

- `php -l resources/views/audit-logs/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/audit-logs`: first column is Event (`[filled circle logo]
  [text]`), followed by Time, User, Description, Subject, eye; column widths
  unchanged; filters/chips/pagination/eye rows still work.

---

## Layout Tweaks: Logo 50px, Eye Column 5%, Cell Padding 10px 11px

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-15

### Type of Change `(required)`

- Bug fix / UI tweak

### Requested By / Source `(optional)`

- Requested by user (`/audit-logs`)

### Problem `(required)`

- Fine-tuning: logo circle wanted at 50px (was 52px), eye-column td wanted at
  5% width (was 7%), table cell padding wanted `10px 11px` (was `10px 18px`).
- Location: `/audit-logs` table

### Root Cause `(required)`

- N/A (styling request)

### Files Changed `(required)`

- `resources/views/audit-logs/index.blade.php`
- `documents/AuditLogsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/audit-logs/index.blade.php`

1. `.al-logo`: width/height 52px -> **50px** (icon inside stays 32px).
2. Eye column (`th/td:nth-child(6)`): width 7% -> **5%**.
3. `.al-table td`: padding `10px 18px` -> **`10px 11px`**.
4. Event cell padding override (`td:nth-child(1)`): horizontal updated
   `18px` -> `11px` to match the new base padding (vertical stays 0 so the
   50px logo equals the row height).

### Behavior of the New Changes `(required)`

| Aspect | Before | After |
|---|---|---|
| Logo circle | 52px | 50px |
| Eye column | 7% | 5% |
| Cell padding (base) | 10px 18px | 10px 11px |
| Event cell horizontal padding | 18px | 11px |

Column width total is now 98% (Event 13 + Time 12 + User 16 + Description 34 +
Subject 18 + Eye 5); the table fills the remaining 2% automatically.

### Impact & Risk `(required)`

- Affects: `/audit-logs` page only.
- Risk: low - CSS-only tweaks; no controller/route/data changes.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Mapping logic, colors, chips, filters, pagination, collapse rows, theme.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; hard reload (Ctrl+F5) to see the change.

### Testing / Verification `(required)`

- `php -l resources/views/audit-logs/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/audit-logs`: logo circle 50px (matches row height), eye
  column 5%, new cell padding; rest unchanged.

---

## Event Tags: Transparent Logo Circle (Colored Border) + Filled Colored Title Pill

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-15

### Type of Change `(required)`

- Feature (UI change)

### Requested By / Source `(optional)`

- Requested by user (`/audit-logs`)

### Problem `(required)`

- The Event Tag Title (text) was plain dark text outside any pill, while the
  semantic color lived in the Event Logo circle's background. Wanted: the
  title inside a **colored pill matching the logo color**, and the logo circle
  background removed (transparent) with its border taking the semantic color.
- Location: `/audit-logs` Event column

### Root Cause `(required)`

- N/A (styling request)

### Files Changed `(required)`

- `resources/views/audit-logs/index.blade.php`
- `documents/AuditLogsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/audit-logs/index.blade.php`

**Before:** `.al-logo` circle was filled with the semantic color
(`background: #22a85b/#ef4444/#6b7280`) and had a 2px white border; the label
`.al-event-text` was plain dark text with no pill.

**After:**

1. `.al-logo` no longer uses a static white border nor a background fill; the
   2px border is now set per semantic class:
   - `.al-logo { border: 2px solid; }`
   - `.al-logo.success { border-color:#22a85b }`, `.danger
     { border-color:#ef4444 }`, `.neutral { border-color:#6b7280 }`
   - circle stays 50x50 (transparent background), icon 32px centered.
2. `.al-event-text` replaced by `.al-event-tag` - a **filled colored pill**:
   height 28px, border-radius 14px, padding 0 14px, white bold text, 10px left
   margin, `vertical-align: middle`, with the matching semantic background
   (`.al-event-tag.success/.danger/.neutral`).
3. Markup: `<span class="al-event-tag $pillClass">$pillText</span>` - the tag
   pill always carries the semantic class, including fallback rows with no
   icon.

### Behavior of the New Changes `(required)`

| Row type | Before | After |
|---|---|---|
| Event logo holder | filled colored circle + white border | transparent circle + colored 2px border + icon |
| Event tag title | plain dark text | filled colored pill matching the logo color, white text |
| Fallback (no icon) | plain text | colored pill only |

Both the circle border and the title pill now share the same semantic color
(green/red/gray).

### Impact & Risk `(required)`

- Affects: `/audit-logs` page only.
- Risk: low - view-only CSS + markup; no controller/route/data changes.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Mapping logic, chips, filters, pagination, collapse rows, other columns,
  theme.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; hard reload (Ctrl+F5) to see the change.

### Testing / Verification `(required)`

- `php -l resources/views/audit-logs/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/audit-logs`: Event column shows `[transparent circle w/
  colored border + icon][filled colored title pill]` in matching semantic
  colors; fallback rows show the colored pill only; chips/filters/pagination/
  eye rows still work.

---

## Logo Holder Border Removed + 30px Icons + Header (Operations Console / Audit Logs Title) Removed

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-15

### Type of Change `(required)`

- Feature (UI change)

### Requested By / Source `(optional)`

- Requested by user (`/audit-logs`)

### Problem `(required)`

- Logo holder still had a 2px semantic-color border; logo images were 32px;
  the page top had an "Operations console" kicker + "Audit Logs" title that the
  user wanted gone.
- Location: `/audit-logs`

### Root Cause `(required)`

- N/A (styling request)

### Files Changed `(required)`

- `resources/views/audit-logs/index.blade.php`
- `documents/AuditLogsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/audit-logs/index.blade.php`

1. `.al-logo` (50x50 circle): removed `border: 2px solid;` and the unused
   `.al-logo.success/.danger/.neutral` border-color rules - the circle now has
   **no border** (transparent background retained).
2. `.al-logo img`: width/height 32px -> **30px** (`object-fit: contain` stays).
3. Removed the header block: markup
   `<div class="al-top"><div class="al-kicker">Operations console</div>
   <div class="al-title">Audit Logs</div></div>` and the now-unused CSS for
   `.al-top`, `.al-kicker`, `.al-title`. The toolbar card is now the top
   element of the page body.

### Behavior of the New Changes `(required)`

| Aspect | Before | After |
|---|---|---|
| Logo holder | 2px colored border | no border (transparent) |
| Logo image | 32x32 | 30x30 |
| Page header | Operations console + Audit Logs title | removed; toolbar card first |

### Impact & Risk `(required)`

- Affects: `/audit-logs` page only.
- Risk: low - CSS/markup only; no controller/route/data changes.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Mapping logic, colored title pills, chips, filters, pagination, collapse
  rows, other columns, theme.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; hard reload (Ctrl+F5) to see the change.

### Testing / Verification `(required)`

- `php -l resources/views/audit-logs/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/audit-logs`: logo holder borderless, icons 30px, no
  "Operations console"/"Audit Logs" header, rest unchanged.

---

## Properties Panel: JSON Dump Replaced with Formatted Key-Value List

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-15

### Type of Change `(required)`

- Feature (UI change)

### Requested By / Source `(optional)`

- Requested by user (`/audit-logs`); data shapes documented in
  `documents/dataRetrieved.txt`

### Problem `(required)`

- The eye-button panel (`.al-properties`) printed the raw stored JSON
  (`json_encode(...  JSON_PRETTY_PRINT)`) with quoted keys/syntax. Wanted a
  readable label format for every attribute, e.g. `Status:     Paid`.
- Location: `/audit-logs` expandable properties rows

### Root Cause `(required)`

- N/A (formatting request)

### Files Changed `(required)`

- `resources/views/audit-logs/index.blade.php`
- `documents/AuditLogsChange.md` (this log)

### What Parts Changed `(required)`

#### `resources/views/audit-logs/index.blade.php`

**Before:** collapse row contained `<pre>{{ json_encode($activity->properties->
toArray(), JSON_PRETTY_PRINT) }}</pre>`.

**After:** the collapse row now renders a formatted list via a small Blade
`@php` formatter + new CSS:

1. CSS: replaced `.al-properties pre` with `.al-props`, `.al-props-group`,
   `.al-props-group-title`, `.al-props-row`, `.al-props-key` (fixed 230px,
   bold, `Label:`), `.al-props-val` (wraps). Container keeps its gray
   `.al-properties` background.
2. Markup/logic: for each top-level group (`attributes`, `old`) render a bold
   section title (capitalized), then one `Label:   value` row per field.
   - Keys humanized: `status` -> `Status`, `updated_at` -> `Updated At`,
     `paymongo_session_ids` -> `Paymongo Session Ids`.
   - Values: `null` -> `-`; booleans -> `true/false`; arrays (e.g.
     `paymongo_session_ids`) -> `["cs_...", "..."]`; scalars/strings as stored.
   - Top-level scalar entries (unexpected) fall back to a single row.

Example for a "paid" update row:

```
Attributes
Status:           Paid
Updated At:       2026-09-07T06:15:53.000000Z

Old
Status:           issued
Updated At:       2026-09-07T05:59:36.000000Z
```

### Behavior of the New Changes `(required)`

| Aspect | Before | After |
|---|---|---|
| Properties panel | raw JSON in `<pre>` | formatted `Label:  value` rows |
| Grouping | JSON braces/brackets | `Attributes` / `Old` section titles |
| null values | `null` in JSON | `-` |
| Arrays | `["a","b"]` JSON | `["a", "b"]` inline list |
| Alignment | JSON indentation | aligned key column (230px) |

### Impact & Risk `(required)`

- Affects: `/audit-logs` page only.
- Risk: low - view-only formatting; no controller/route/data changes. All 5
  stored data shapes (created citation/payment, PayMongo updates, paid-status
  update, deleted payment) render through the same formatter.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Eye-button render rule, mapping/pills, chips, filters, pagination, other
  columns, theme.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; hard reload (Ctrl+F5) to see the change.
- Long values (e.g. growing `paymongo_session_ids`) wrap within the value
  column; reduce `.al-props-key` flex-basis if labels crowd on narrow screens.

### Testing / Verification `(required)`

- `php -l resources/views/audit-logs/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA on `/audit-logs`: expand rows for a created, updated (PayMongo),
  paid-update, and deleted entry - each shows capitalized labels with aligned
  values grouped under `Attributes`/`Old`; no raw JSON braces; `-` for nulls;
  session-id arrays shown inline.

---

## Pagination: 10 Entries Per Page

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-15

### Type of Change `(required)`

- Feature (config change)

### Requested By / Source `(optional)`

- Requested by user (`/audit-logs`)

### Problem `(required)`

- The audit log list showed 30 entries per page; wanted 10 per page.
- Location: `/audit-logs`

### Root Cause `(required)`

- Pagination size was hard-coded in the controller.

### Files Changed `(required)`

- `app/Http/Controllers/AuditLogController.php`

### What Parts Changed `(required)`

- Line 48: `$query->paginate(30)` -> `$query->paginate(10)`.

### Behavior of the New Changes `(required)`

| Aspect | Before | After |
|---|---|---|
| Entries per page | 30 | 10 |
| Pagination links | pages of 30 | pages of 10 |
| Entry count ("Showing X of Y") | 30 per page | 10 per page |

### Impact & Risk `(required)`

- Affects: `/audit-logs` only. Low risk - pagination/entry-count logic in the
  view uses the paginator, so it adapts automatically. Means more pages (98
  entries -> ~10 pages).

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Filters, chips, table layout, properties panel, theme.

### Known Issues / Follow-ups `(optional)`

- None

### Testing / Verification `(required)`

- `php -l app/Http/Controllers/AuditLogController.php`
- Load `/audit-logs`: 10 rows per page, pagination works, entry count correct.

---

## Column Widths: Event 17%, Subject (al-subject) 14%

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-15

### Type of Change `(required)`

- Feature (UI change)

### Requested By / Source `(optional)`

- Requested by user (`/audit-logs`)

### Problem `(required)`

- Wanted the Event column at 17% and Subject column reduced by 4%.
- Location: `/audit-logs` table

### Root Cause `(required)`

- N/A (styling request)

### Files Changed `(required)`

- `resources/views/audit-logs/index.blade.php`
- `documents/AuditLogsChange.md` (this log)

### What Parts Changed `(required)`

- Line 225: Event `th/td:nth-child(1)` width 13% -> **17%** (+4%).
- Line 229: Subject `th/td:nth-child(5)` (carries `.al-subject`) width 18% ->
  **14%** (-4%).
- Column total remains 98% (13+12+16+34+18+5 -> 17+12+16+34+14+5); the table
  auto-fills the remaining 2%.

### Behavior of the New Changes `(required)`

| Column | Before | After |
|---|---|---|
| Event (1) | 13% | 17% |
| Subject (5) | 18% | 14% |

### Impact & Risk `(required)`

- Affects: `/audit-logs` page only. Low risk - CSS-only; no data changes.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Time/User/Description/Eye widths, mapping, pills, properties panel,
  filters, pagination.

### Known Issues / Follow-ups `(optional)`

- Browsers may cache old CSS; hard reload (Ctrl+F5) to see the change.

### Testing / Verification `(required)`

- `php -l resources/views/audit-logs/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Load `/audit-logs`: Event column visibly wider (17%), Subject column
  narrower (14%), layout intact.