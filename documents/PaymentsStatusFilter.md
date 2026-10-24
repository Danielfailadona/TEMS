# Payments Status Filter

### Date Edited / Applied `(required)`

- Edited and applied: 2026-10-19

### Type of Change `(required)`

- Feature (new status filter on the `/payments` filter panel)

### Requested By / Source `(optional)`

- Requested by the repository owner: add a status filter to `/payments`.

### Problem `(required)`

- The `/payments` filter panel could filter by category (citation/clamping/impounding),
  payment method, date range, online-only, and search — but not by the value shown in
  the **Status** column.
- Location: `/payments` → Filters panel → (previously) Methods / Categories / Date /
  Activity Status.

### Root Cause `(required)`

- Not a defect. The Status column shows two different sources that made a naive
  `where('status', …)` query impossible:
  - Receipt rows derive their status in `Payment::getStatusLabel()`
    (`Paid` = has `paid_at`; `Pending` = online payment without `paid_at`;
    `Unpaid` = no `paid_at` and not online).
  - Outstanding rows show the payable enum label
    (`CitationStatus`: Issued/Overdue/Clamped; `ClampingStatus`: Awaiting Payment;
    `ImpoundingStatus`: Impounded/Awaiting Payment).
- Because the grid is assembled in memory from three queries and then paginated, the
  filter is applied to the merged collection rather than to a single query.

### Files Changed `(required)`

- `app/Http/Controllers/PaymentController.php`
- `resources/views/payments/index.blade.php`
- `documents/PaymentsStatusFilter.md` (this log)

### What Parts Changed `(required)`

#### Controller — status filter after merge (`app/Http/Controllers/PaymentController.php`)

Added an in-memory filter on the merged `$all` collection (before pagination), plus an
early-return in `pendingRows()` for receipt-only statuses.

**Before:**

```php
$all = $receipts->concat($pending)->sortByDesc('date')->values();

$perPage = 5;
```

**After:**

```php
$all = $receipts->concat($pending)->sortByDesc('date')->values();

// Status filter: matches the label shown in the Status column. Receipts
// use their derived status (paid/pending/unpaid); outstanding rows use
// the payable enum value (issued|overdue|clamped|impounded|awaiting_payment).
if ($request->filled('status')) {
    $status = $request->status;
    $all = $all->filter(function ($row) use ($status) {
        $key = $row['kind'] === 'receipt'
            ? ($row['payment']->paid_at ? 'paid' : ($row['payment']->isOnlinePayment() ? 'pending' : 'unpaid'))
            : $row['payable']->status->value;

        return $key === $status;
    })->values();
}

$perPage = 5;
```

**`pendingRows()` early-return (query-saving):**

**Before:**

```php
if ($request->filled('payment_method') || $request->filled('online')) {
    return collect();
}
```

**After:**

```php
if (
    $request->filled('payment_method')
    || $request->filled('online')
    || in_array($request->input('status'), ['paid', 'pending', 'unpaid'], true)
) {
    return collect();
}
```

#### View — filter panel field (`resources/views/payments/index.blade.php`)

Added a new **Status** select field immediately after the Categories fieldset, reusing
the existing `.pym-field` / `.pym-select` styles:

**After:**

```html
<fieldset class="pym-field">
    <label class="pym-field__label" for="pymStatus">Status</label>
    <div class="pym-select">
        <select id="pymStatus" name="status">
            <option value="">All Statuses</option>
            <option value="paid">Paid</option>
            <option value="pending">Pending</option>
            <option value="unpaid">Unpaid</option>
            <option value="issued">Issued</option>
            <option value="overdue">Overdue</option>
            <option value="clamped">Clamped</option>
            <option value="impounded">Impounded</option>
            <option value="awaiting_payment">Awaiting Payment</option>
        </select>
        ...
    </div>
</fieldset>
```

(each `<option>` carries `{{ request('status') === '<value>' ? 'selected' : '' }}`)

#### View — active-filter chip (`resources/views/payments/index.blade.php`)

Added `status` to the tracked query keys (`$qs`) and a matching chip with a human
label map, so an applied status appears in the chips row and can be removed.

**Before:**

```php
$qs = request()->only(['search', 'category', 'payment_method', 'date_from', 'date_to', 'online']);
```

**After:**

```php
$qs = request()->only(['search', 'category', 'payment_method', 'status', 'date_from', 'date_to', 'online']);

// …

if (($qs['status'] ?? null) !== null) {
    $statusLabels = [
        'paid' => 'Paid', 'pending' => 'Pending', 'unpaid' => 'Unpaid', 'issued' => 'Issued',
        'overdue' => 'Overdue', 'clamped' => 'Clamped', 'impounded' => 'Impounded',
        'awaiting_payment' => 'Awaiting Payment',
    ];
    $activeFilters[] = ['label' => $statusLabels[$qs['status']] ?? \Illuminate\Support\Str::headline($qs['status']), 'href' => $baseUrl(['status'])];
}
```

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Status filter control | Did not exist | "Status" dropdown after Categories |
| Filter by "Paid" | N/A | Shows receipt rows with `paid_at` set |
| Filter by "Pending" | N/A | Shows online receipts awaiting payment |
| Filter by "Unpaid" | N/A | Shows non-online receipts without `paid_at` |
| Filter by "Issued"/"Overdue"/"Clamped" | N/A | Shows matching outstanding citation rows |
| Filter by "Impounded" | N/A | Shows outstanding impounded rows |
| Filter by "Awaiting Payment" | N/A | Shows outstanding clamping and impounding rows |
| Applied status | N/A | Shown as a removable chip; "Clear Filter" clears it |
| Combine with other filters | N/A | Composes with category/method/date/online/search |

### Impact & Risk `(required)`

- Affects: the `/payments` page only (filtering). No other pages, roles, or resources.
- Risk: low. Filtering is read-only and applied after the existing merge; it does not
  alter the receipt/pending queries, sorting, pagination, or actions.
- The `pendingRows()` early-return only skips work for receipt-only statuses
  (`paid`/`pending`/`unpaid`), whose rows can never match outstanding records anyway.
- No CSS/Vite rebuild required (styles reused; script unchanged).

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `Payment::getStatusLabel()` / `getStatusBadgeClass()`, the status enums
- Receipt/pending queries (`awaitingCitationsQuery`, `awaitingClampingQuery`,
  `awaitingImpoundingQuery`) and `PaymentController` actions other than `index`
- Filter panel JS, `.pym-select` styles, table row rendering

### Known Issues / Follow-ups `(optional)`

- A citation that has a *pending online* payment can appear twice in the grid today (as
  a `Pending` receipt and as an `Issued`/`Overdue`/`Clamped` outstanding row). This is
  pre-existing; the new `Pending` and record-status options filter each occurrence
  independently.
- `Awaiting Payment` intentionally covers both clamping and impounding outstanding rows
  (both enums use the `awaiting_payment` value).

### Testing / Verification `(required)`

- `php -l app/Http/Controllers/PaymentController.php` — clean.
- `php -l resources/views/payments/index.blade.php` — clean.
- `php artisan view:cache` → "Blade templates cached successfully"; then
  `php artisan view:clear`.
- Full suite: `& "C:\php-8.3\php.exe" "vendor/phpunit/phpunit/phpunit"` — 14 tests /
  34 assertions, all passed.
- Manual: on `/payments`, open Filters → choose each Status → Apply Filter → the URL
  gains `?status=…`, the grid is filtered, and a removable chip appears.
