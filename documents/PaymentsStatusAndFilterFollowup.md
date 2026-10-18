# Payments Status Tags Simplification + Filters Applied to Unpaid Rows

### Date Edited / Applied `(required)`

- Edited and applied: 2026-10-13

### Type of Change `(required)`

- Feature / bug fix (status labels restored on /payments; filters now honor unpaid rows)

### Requested By / Source `(optional)`

- Requested by the repository owner while reviewing /payments (dropdown filter behavior
  and status tags such as "Issued", "Paid", "Awaiting Payment")

### Problem `(required)`

- The rebuilt /payments table (mockup style) removed the status tags. Only a generic
  amber "Unpaid" pill remained for outstanding tickets, so real statuses such as
  "Issued", "Overdue", "Clamped", "Awaiting Payment", "Impounded", and "Waiting Release"
  were hidden.
- Additionally, the filter dropdown only affected paid receipts. Method, date-range, and
  "Online only" filters left unpaid ("Collect") rows on screen with a `—` method cell,
  which made filtering appear broken.
- Location: `/payments` (PaymentController@index → payments/index.blade.php)

### Root Cause `(required)`

- Status: the mockup table has no Status column, and the rebuild replaced the old
  per-row status badge with a single hard-coded "Unpaid" pill, losing the enum-driven
  labels/colors (`CitationStatus::badgeClass()`, `ClampingStatus::badgeClass()`,
  `ImpoundingStatus::badgeClass()`, `Payment::getStatusBadgeClass()`/`getStatusLabel()`).
- Filters: `PaymentController::pendingRows()` only considered `category` and `search`;
  it never applied `payment_method`, `online`, or the date range, so unpaid rows ignored
  those filters.

### Files Changed `(required)`

- `app/Http/Controllers/PaymentController.php`
- `resources/views/payments/index.blade.php`

### What Parts Changed `(required)`

#### `app/Http/Controllers/PaymentController.php` — `pendingRows()`

**Before:** only `category` + `search` filtered the unpaid collection; method/online/date
were ignored.

**After:**

- Early return `collect()` when `payment_method` or `online` is set (unpaid rows have
  neither).
- When a full `date_from`/`date_to` range is supplied, filter the pending rows by their
  own mapped `date` (issued/clamped/impounded), using the same
  UTC start/end-of-day bounds as the receipts query.

```php
if ($request->filled('payment_method') || $request->filled('online')) {
    return collect();
}

$dateFrom = $request->filled('date_from') && $request->filled('date_to')
    ? Carbon::parse($request->date_from)->startOfDay()->setTimezone('UTC')
    : null;
$dateTo = $request->filled('date_from') && $request->filled('date_to')
    ? Carbon::parse($request->date_to)->endOfDay()->setTimezone('UTC')
    : null;

// ... existing per-category collection ...

if ($dateFrom !== null && $dateTo !== null) {
    $rows = $rows->filter(fn ($row) => $row['date'] !== null && $row['date']->between($dateFrom, $dateTo));
}

return $rows;
```

#### `resources/views/payments/index.blade.php` — Receipt # cell + pill CSS

**Before:** receipts showed only the receipt number; unpaid rows showed a hard-coded
amber pill:

```blade
@if ($isReceipt)
    {{ $payment->receipt_number }}
@else
    <span class="pym-pill pym-pill--unpaid">Unpaid</span>
@endif
```

**After:** both row kinds render the real status using the badge classes/labels the row
already carries (the same pattern as Citations/Clamping pages):

```blade
@if ($isReceipt)
    <span class="pym-pill {{ $payment->getStatusBadgeClass() }}">{{ $payment->getStatusLabel() }}</span> {{ $payment->receipt_number }}
@else
    <span class="pym-pill {{ $payable->status->badgeClass() }}">{{ $payable->status->label() }}</span>
@endif
```

Removed the `.pym-pill--unpaid` CSS rule (no longer used); `.pym-pill` base remains and
picks up color from the `tag-*` / Bootstrap `bg-*` badge classes.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Outstanding ticket row | Generic amber "Unpaid" pill | Real status: "Issued", "Overdue", "Clamped", "Awaiting Payment", "Impounded", "Waiting Release" (existing tag/bg colors) |
| Paid receipt row | Receipt number only | Green "Paid" badge + receipt number |
| Filter by Method | Unpaid rows stayed visible | Unpaid rows drop (they have no method) |
| Filter "Online only" | Unpaid rows stayed visible | Unpaid rows drop (not online payments) |
| Filter by date range | Only receipts filtered | Receipts + unpaid rows filtered by their issue/clamp/impound date |

### Impact & Risk `(required)`

- Affects: /payments for all roles with `viewAny` on Payment.
- Risk: moderate — `pendingRows()` now drops all unpaid rows under method/online filters;
  this is the intended behavior but users should know the "Collect" section disappears
  under those filters. Date-range behavior matches the receipts query's timezone bounds.
- Pending-online receipts (no `paid_at`, `paymongo_checkout_id` set) show an amber
  "Pending" badge instead of "Paid" — correct and intentional.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Enums (`CitationStatus`, `ClampingStatus`, `ImpoundingStatus`, `PaymentMethod`),
  `app/Models/Payment.php`, routes, policies, seeders, DB schema
- The filter panel markup/JS and table layout in `payments/index.blade.php`
- `resources/css/app.css` (styles remain scoped inline; no Vite rebuild)

### Known Issues / Follow-ups `(optional)`

- The mockup's GCash/Credit Card payment methods do not exist in `PaymentMethod`; those
  dropdown options were intentionally not added.
- A receipt badge uses Bootstrap `bg-*` classes while unpaid badges use `tag-*` classes;
  both are styled by `.pym-pill`, so they render consistently.

### Testing / Verification `(required)`

- `php artisan view:cache` → "Blade templates cached successfully"; then
  `php artisan view:clear`.
- Full suite: `php -d extension_dir="C:\php-8.3\ext" -d extension=mbstring -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit`
  → 14 tests / 34 assertions, all passed.