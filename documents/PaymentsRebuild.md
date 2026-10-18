# Payments Index Rebuild to Match Design Mockup

### Date Edited / Applied `(required)`

- Edited and applied: 2026-10-13

### Type of Change `(required)`

- Feature / refactor (UI redesign of /payments index to match `testPages/Payments (2).html`)

### Requested By / Source `(optional)`

- Requested by the repository owner during review of `testPages/Payments (2).html`
- Reference: https://transenfo-1.onrender.com/payments (live page is behind login; the
  reference for this change is the local mockup file)

### Problem `(required)`

- The Payments page (`resources/views/payments/index.blade.php`) was a card grid with an
  inline Bootstrap filter form, which did not match the approved mockup design.
- Mockup calls for a **table** (Receipt #, Citation #, Vehicle, Amount, Method, Paid At,
  Actions) with a **Filters dropdown panel** (methods, categories, dd/mm/yyyy date range,
  online-only checkbox), active-filter **chips**, and a "Showing X to Y of Z results"
  footer with pagination.
- Location: `/payments` (PaymentController@index → payments/index.blade.php)

### Root Cause `(required)`

- The existing view used a `row-cols-*` card layout and a full-width inline `<form>`
  filter bar. It was never updated to the table + dropdown-filter pattern already used by
  the rebuilt Citations page.

### Files Changed `(required)`

- `resources/views/payments/index.blade.php`

### What Parts Changed `(required)`

#### `resources/views/payments/index.blade.php`

**Before:** Card grid (`row-cols-*` of `.payment-card`), inline Bootstrap filter form
(search, method select, category select, two native date inputs, online checkbox,
Apply/Reset buttons), centered default Bootstrap pagination.

**After:** Standalone `.pym-dash` scoped design mirroring the Citations page:

- **Toolbar (GET form):** Filters dropdown + search box + "Record Payment" pill button.
- **Filter panel (dropdown):** Methods select (from `PaymentMethod` enum, not hard-coded),
  Categories select (citation/clamping/impounding), date range as masked dd/mm/yyyy text
  inputs backed by hidden native date inputs (`date_from`/`date_to` keep the same names
  the controller already reads), "Online only" globe checkbox, Apply Filter / Clear
  Filter actions. Client-side dd/mm/yyyy validation on Apply.
- **Active-filter chips:** server-rendered links that drop a single filter param while
  keeping the rest.
- **Table:** the same 7 columns as the mockup. Paid receipts show `receipt_number`,
  `payableNoticeNumber()`, `payableVehicle()`, amount, method (online-aware), paid date,
  View/Edit pill buttons. Outstanding tickets (`kind === 'pending'`) show an amber
  "Unpaid" pill in the receipt column, the citation/notice #, plate, the amount due
  (same per-category `$due` logic as the old cards), "—" for method/date label, plus a
  View + blue "Collect" pill button.
- **Footer:** "Showing X to Y of Z results" + Bootstrap 5 pagination, styled scoped.

The data flow is fully server-side and unchanged: the hardcoded `qs` for preset chips is
rebuilt from `request()->only([...])`; controller, models, routes, policies, and the
pagination object (`$grid`) are untouched.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| /payments default | Card grid, newest first | Table with Receipt #, Citation #, Vehicle, Amount, Method, Paid At, Actions |
| Filter by method | Inline select, auto-submit | Filters dropdown → Apply Filter |
| Filter by category | Inline select | Dropdown select (Citation Fine / Clamping Fee / Impounding Fee) |
| Filter by date range | Two native date inputs | dd/mm/yyyy masked inputs + calendar buttons (hidden native date still posts same params) |
| Online-only | Inline checkbox | "Online only" globe checkbox in dropdown |
| Remove one filter | Full Reset link | Clickable chips that drop a single param |
| Outstanding tickets | Card with "Collect" | Same data in table: amber "Unpaid" pill + blue "Collect" button |
| Pagination | Centered default links | Bottom-right Bootstrap 5 pagination + "Showing X to Y of Z results" |

### Impact & Risk `(required)`

- Affects: `/payments` for all roles with `viewAny` on Payment.
- Risk: the rebuilt view relies on the same controller variables (`$grid`, `$row`
  shape) — if the controller's row shape changes, the table cells must be re-checked.
  The date inputs still submit `date_from`/`date_to` as YYYY-MM-DD so the controller's
  existing date parsing is unaffected.
- The `.pym-dash` scoped styles mirror the Citations page classes; JS is page-scoped and
  runs inside `DOMContentLoaded`.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `PaymentController.php` (index/create/store/edit/update/show logic)
- `app/Models/Payment.php`, routes, policies, seeders, DB schema
- `payments/create.blade.php`, `payments/show.blade.php`, `payments/edit.blade.php`,
  `payments/print.blade.php`
- `resources/css/app.css` (styles are scoped inline in the view; no Vite rebuild needed)
- Payment method enum values: only Cash / Check / Bank Transfer / Other exist in the app;
  the mockup's "GCash"/"Credit Card" options were intentionally not added.

### Known Issues / Follow-ups `(optional)`

- The live page (https://transenfo-1.onrender.com/payments) is behind the login wall, so
  a visual comparison against the deployed site requires an authenticated session.
- "Activity Status" checkbox in the mockup is a demo-only control; it is mapped to the
  real "Online only" filter.
- The mockup lists GCash / Credit Card as methods; the app has no such enum values, so
  those were skipped (see Untouched).

### Testing / Verification `(required)`

- `php artisan view:cache` → "Blade templates cached successfully"; then
  `php artisan view:clear`.
- Full suite: `php artisan` equivalent via phpunit directly —
  `php -d extension_dir="C:\php-8.3\ext" -d extension=mbstring -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit`
  → 14 tests / 34 assertions, all passed.