# Payments Table: Replace Receipt # Column with Status Column

### Date Edited / Applied `(required)`

- Edited and applied: 2026-10-13

### Type of Change `(required)`

- Refactor / UI change (column rename + remove receipt number from table)

### Requested By / Source `(optional)`

- Requested by the repository owner while reviewing /payments

### Problem `(required)`

- The first column of the /payments table was labelled "Receipt #" and showed both the
  status badge and the receipt number for paid rows. The owner asked to convert it to a
  dedicated "Status" column and drop the receipt IDs from the list.
- Location: `/payments` → `resources/views/payments/index.blade.php`

### Root Cause `(required)`

- The mockup rebuild introduced a 7-column table with "Receipt #" as column 1; keeping
  the receipt number there alongside the status badge was not part of the desired layout.

### Files Changed `(required)`

- `resources/views/payments/index.blade.php`

### What Parts Changed `(required)`

#### `resources/views/payments/index.blade.php`

**Before:** header `<th>Receipt #</th>`; cell rendered badge + receipt number for paid
rows, badge only for unpaid rows; column widths were col1 16%, col2 19%.

**After:** header `<th>Status</th>`; both row kinds render only the status badge (no
receipt number); widths rebalanced to col1 12%, col2 23% (total stays 100%).

```blade
<!-- Before -->
<th scope="col">Receipt #</th>
...
<td>
    @if ($isReceipt)
        <span class="pym-pill {{ $payment->getStatusBadgeClass() }}">{{ $payment->getStatusLabel() }}</span> {{ $payment->receipt_number }}
    @else
        <span class="pym-pill {{ $payable->status->badgeClass() }}">{{ $payable->status->label() }}</span>
    @endif
</td>

<!-- After -->
<th scope="col">Status</th>
...
<td>
    @if ($isReceipt)
        <span class="pym-pill {{ $payment->getStatusBadgeClass() }}">{{ $payment->getStatusLabel() }}</span>
    @else
        <span class="pym-pill {{ $payable->status->badgeClass() }}">{{ $payable->status->label() }}</span>
    @endif
</td>
```

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Paid receipt row | "Paid" badge + receipt number | "Paid" badge only |
| Unpaid ticket row | Status badge only | Status badge only (unchanged) |
| First column header | "Receipt #" | "Status" |
| Receipt number visibility | Visible in list | Not visible on /payments (still on detail page + searchable) |

### Impact & Risk `(required)`

- Affects: /payments table for all roles with `viewAny` on Payment.
- Risk: low. Search still matches `receipt_number` (controller unchanged), and the
  receipt number remains available on the payment detail page (`payments.show`) and in
  the View/Edit buttons' `aria-label`s (hidden from display). Column width shift gives
  the Citation # column more room; no other columns changed.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `PaymentController.php` (all filter/search/pending behavior)
- `Payment`, enums, routes, policies, other payments views
- `resources/css/app.css` (styles remain scoped inline; no Vite rebuild)

### Known Issues / Follow-ups `(optional)`

- Receipt numbers are no longer visible in the list; if needed later, they can be
  re-added under the View action or as a tooltip.

### Testing / Verification `(required)`

- `php artisan view:cache` → "Blade templates cached successfully"; then
  `php artisan view:clear`.
- Full suite: `php -d extension_dir="C:\php-8.3\ext" -d extension=mbstring -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit`
  → 14 tests / 34 assertions, all passed.