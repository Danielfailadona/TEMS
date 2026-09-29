# Payments Change Log

Change documentation for the Payments system. Follow the template in
`documents/documentation_Reference.md`. Append new entries at the end, newest last.

---

## Payments Index Redesigned to Dashboard Mockup (Search-Only)

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-21

### Type of Change `(required)`

- Feature / UI redesign (styling + server-side search)

### Requested By / Source `(optional)`

- Requested by user to match `testPages/Payments_HTML_CSS_JS_Recreated.html`
  (static design mockup for the Payments console page). Live page: `/payments`.
- Scope decision by user: **search only** — the mockup's status filter dropdown
  was dropped as not applicable (payments list only shows completed payments;
  Issued/Overdue/Clamped are unpaid citation states).

### Problem `(required)`

- The live payments list used a plain Bootstrap card/table and had **no search**
  — with real payment volume, locating a record by receipt/citation/plate was
  not possible from the index. The page did not match the mockup's console look.

### Root Cause `(required)`

- `PaymentController@index` was a bare `whereNotNull('paid_at')->latest()->paginate(10)`
  with no `search` filter or query-string persistence, and the view used layout
  defaults with no deliberate styling.

### Files Changed `(required)`

- `app/Http/Controllers/PaymentController.php` (only `index()`)
- `resources/views/payments/index.blade.php`
- `documents/PaymentsChange.md` (this log)

### What Parts Changed `(required)`

#### `app/Http/Controllers/PaymentController.php`

`index()` — **Before:**

```php
$query = Payment::with(['citation', 'cashier'])->whereNotNull('paid_at');

$payments = $query->latest('paid_at')->paginate(10);
```

**After:**

```php
$query = Payment::with(['citation', 'cashier'])
    ->whereNotNull('paid_at')
    ->when($request->search, function ($q, $search) {
        $q->where(function ($inner) use ($search) {
            $inner->where('receipt_number', 'like', "%{$search}%")
                ->orWhereHas('citation', function ($c) use ($search) {
                    $c->where('citation_number', 'like', "%{$search}%")
                      ->orWhere('vehicle_plate', 'like', "%{$search}%");
                });
        });
    });

$payments = $query->latest('paid_at')->paginate(10)->withQueryString();
```

- `receipt_number` is a column on `payments`; `citation_number` / `vehicle_plate`
  live on the `citation` relation, hence `whereHas('citation', ...)`.
- `->withQueryString()` keeps `?search=` across pagination pages.

#### `resources/views/payments/index.blade.php`

Full rewrite with a page-scoped `.pay-dash` `@push('styles')` block (toolbar,
search box, record pill, table 44px header / 90px rows / fixed widths
`24/20/10/12/10/12/12%`, entry-count bar, view/edit icon pills, restyled
square pagination, responsive media queries).

- **Toolbar** (`form#payForm` GET): search box whose **blue magnify icon is a
  `type="submit"` button** (applies the search; Enter and a 300ms debounced
  auto-submit also work); placeholder "Receipt #, citation # or plate...".
  "＋ Record Payment" blue pill = `@can('create')` link to `payments.create`.
  No Filters dropdown.
- **Table**: Receipt # · Citation # · Vehicle · `₱` Amount · Method
  (`payment_method->label()`) · Paid At (`M d, Y`, "Pending" kept) · Actions.
  Entry-count bar shows "Showing X to Y of Z entries".
- **Actions**: two 38x27 rounded icon pills — eye -> `payments.show`,
  pencil -> `payments.edit` gated by `@can('update', $payment)`.
- **Footer**: "Showing X to Y of Z results" + `$payments->links()` styled as
  square page buttons (current page filled blue); empty state preserved.
- **`@push('scripts')`**: debounced search submit only.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Find a payment by receipt/citation/plate | not possible (no search) | search box; submit via blue magnify icon, Enter, or 300ms debounce |
| Search across pages | n/a | `?search=` preserved by pagination (`withQueryString`) |
| Record payment | `btn btn-primary` text-end link | blue pill "＋ Record Payment" link |
| Row actions | "View"/"Edit" outline text buttons | eye/pencil icon pills (Edit still `@can`) |
| Table look | Bootstrap `table-hover`, natural heights | 44px header, 90px rows, fixed widths, `#e5e9ed` borders |
| Counts | none | "Showing X to Y of Z entries" + footer results |
| Pagination | default Bootstrap | square page buttons, current page filled |

### Impact & Risk `(required)`

- Affects: only the `/payments` index page (CSS scoped under `.pay-dash`).
- Risk: low - controller change is additive; no other route uses these params.
  `$request->search` was false previously, so behavior for plain `/payments`
  is unchanged. View-only rewrite otherwise.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `payments.create/edit/show/print` views, `PaymentMethod` enum, models, routes,
  layout shell, `app.css`
- No filter dropdown was added (per user decision)

### Known Issues / Follow-ups `(optional)`

- Browsers may cache the page; a hard reload (Ctrl+F5) shows the new UI.
- Search is server-driven; typing triggers a reload after 300ms — expected for a
  paginated dataset.

### Testing / Verification `(required)`

- `php -l app/Http/Controllers/PaymentController.php`
- `php -l resources/views/payments/index.blade.php`
- `php artisan view:cache` / `view:clear`
- Headless render (authenticated admin, MessageBag shared, real payment data):
  toolbar, search submit, record pill link, table card, entry count, view pill,
  edit pill (via `@can`), pagination, results text all present.
- Manual QA (hard refresh): search via icon / Enter / debounce across pages,
  pagination links retain `?search=`, "Showing X to Y of Z" counts, empty state,
  responsive wrap on small widths.

---

## Payment Receipt Heading Replaced with Back Button

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-21

### Type of Change `(required)`

- UI tweak (navigation)

### Requested By / Source `(optional)`

- Requested by user: on `GET /payments/28` (and every payment show page), remove
  the "Payment Receipt" heading and replace it with a back button to `/payments`.

### Problem `(required)`

- The show page had no way back to the payments list; the `<h1 class="h3 mb-0">`
  "Payment Receipt" heading was purely a static label, not a link.

### Root Cause `(required)`

- The header row in `resources/views/payments/show.blade.php` rendered the heading
  in the left slot; it simply lacked a back link to `payments.index`.

### Files Changed `(required)`

- `resources/views/payments/show.blade.php` (header row)
- `documents/PaymentsChange.md` (this log)

### What Parts Changed `(required)`

Header row of `show.blade.php` (line 7):

**Before:**

```html
<h1 class="h3 mb-0">Payment Receipt</h1>
```

**After:**

```html
<a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Back to Payments
</a>
```

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Navigate from a receipt back to the list | no in-page control | "Back to Payments" button -> `GET /payments` |
| Layout | heading (left) + Edit/Print (right) | Back (left) + Edit/Print (right), still `d-flex justify-content-between` |
| Print receipt | heading hidden (header `no-print`) | back button hidden (header keeps `no-print`) |
| Page title | "Receipt <n>" | unchanged |

### Impact & Risk `(required)`

- Affects: all payment show pages (`/payments/{id}`). Low risk - static swap,
  no logic touched; route `payments.index` already exists.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Receipt card content, print view, Edit/Print buttons, page `<title>`, controller

### Known Issues / Follow-ups `(optional)`

- None

### Testing / Verification `(required)`

- `php -l resources/views/payments/show.blade.php`
- `php artisan view:cache` / `view:clear`
- Manual QA (hard refresh): `/payments/28` shows "Back to Payments" button on the
  left, clicking it returns to `/payments`; Edit/Print still visible on the right;
  print preview omits the button.

---

## Merge Resolution: Search-Only Table Kept + Driver-Name Search

### Date Edited / Applied `(required)`

- Edited and applied: 2026-09-29

### Type of Change `(required)`

- Merge conflict resolution (controller + view)

### Requested By / Source `(optional)`

- During the `origin/master` merge, `PaymentController.php` and
  `payments/index.blade.php` were left in a conflicted (`UU`) state. User
  decided: keep our search-only table; do not adopt the master-side card grid /
  method / date / online filters; port the driver-name search + trimmed input.

### Problem `(required)`

- Two independent copies changed the same code: ours (`whereNotNull('paid_at')` +
  `search` covering receipt/citation #/plate, `.pay-dash` table) vs master's
  (no paid_at filter, `search` incl. driver name, payment_method/date/online
  filters, `paginate(6)`, `.payment-card` grid + `getStatusBadgeClass()` badges).
  Conflict markers broke the page and `php -l` until resolved.

### Root Cause `(required)`

- Overlapping edits on `PaymentController@index` and `payments/index.blade.php`
  during the branch merge; git could not auto-merge them.

### Files Changed `(required)`

- `app/Http/Controllers/PaymentController.php` (only `index()`)
- `resources/views/payments/index.blade.php` (conflict hunk resolved; placeholder updated)

### Changes Made `(required)`

- `PaymentController@index`: kept `Payment::with(['citation','cashier'])->whereNotNull('paid_at')`,
  `latest('paid_at')`, `paginate(10)->withQueryString()`. Search is now trimmed
  and also matches the citation's `driver_name`:

  ```php
  if ($search = trim($request->query('search'))) {
      $query->where(function ($inner) use ($search) {
          $inner->where('receipt_number', 'like', "%{$search}%")
              ->orWhereHas('citation', function ($c) use ($search) {
                  $c->where('citation_number', 'like', "%{$search}%")
                      ->orWhere('vehicle_plate', 'like', "%{$search}%")
                      ->orWhere('driver_name', 'like', "%{$search}%");
              });
      });
  }
  ```

- `payments/index.blade.php`: kept the HEAD `.pay-dash` table entirely (search
  only); discarded master's `.payment-card` grid, its filter panel (payment
  method, date range, online-only, reset) and `paginate(6)`. Search placeholder
  updated to `Receipt #, citation #, plate, or driver...`.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Index scope | paid/completed only (`paid_at` not null) | unchanged |
| Search fields | receipt, citation #, plate | + driver name; trimmed input |
| Layout | mockup table, search only | unchanged |
| Master's grid / method / date / online filters | n/a | discarded |
| Payments per page | 10 | unchanged |

### Impact & Risk `(required)`

- Affects: `/payments` index search behavior only. Low risk. The master-side
  `Payment::getStatusBadgeClass()` / `getStatusLabel()` remain on the model but
  are unused by this view.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Payments create/edit/show/print views, policies, enums, pagination + result
  count footer, Record Payment / eye / edit pills.

### Known Issues / Follow-ups `(optional)`

- None

### Testing / Verification `(required)`

- `php -l app/Http/Controllers/PaymentController.php` - clean.
- `php artisan view:clear` + `view:cache` - all Blade templates compile.
- Headless controller smoke test (authenticated): `index(',')` returns 2
  payments; `index('?search=Dela')` returns 2 without error - search incl.
  driver name runs cleanly.