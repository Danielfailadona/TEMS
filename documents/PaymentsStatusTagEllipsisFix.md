# Payments Status Tag Ellipsis ("...") Fix

### Date Edited / Applied `(required)`

- Edited and applied: 2026-10-13

### Type of Change `(required)`

- Bug fix (status tag rendered with a trailing "…" ellipsis)

### Requested By / Source `(optional)`

- Reported by the repository owner on `/payments`: the "Awaiting Payment" tag showed
  "…" on its right side.

### Problem `(required)`

- On `/payments`, the Status pill for the longest status label ("Awaiting Payment",
  16 characters) was clipped and followed by an ellipsis character "…". Shorter
  labels ("Issued", "Paid", "Overdue", "Clamped", "Released", "Pending", "Unpaid")
  were unaffected.
- Location: `/payments` → `resources/views/payments/index.blade.php`

### Root Cause `(required)`

- Two CSS rules combined:
  1. Every table cell truncates: `.pym-table td { white-space: nowrap; overflow: hidden;
     text-overflow: ellipsis; }`.
  2. The Status column (column 1) was narrowed to `12%` during the earlier
     "Receipt # → Status" change.
- The table uses `table-layout: fixed; min-width: 900px`. At 12% the column content
  box (~100–135px depending on viewport, minus `0 16px` padding) is narrower than the
  "Awaiting Payment" pill (~115–125px at 11px bold + `0 10px` padding).
- The pill is an atomic `inline-block`, so the cell cannot wrap it. With
  `overflow: hidden`, the cell clipped the pill and painted the
  `text-overflow: ellipsis` glyph at the clip edge — which appears just to the right
  of the tag's background. Only the longest label overflowed, so only "Awaiting
  Payment" showed the "…".

### Files Changed `(required)`

- `resources/views/payments/index.blade.php`
- `documents/PaymentsStatusTagEllipsisFix.md` (this log)

### What Parts Changed `(required)`

#### Column widths (`resources/views/payments/index.blade.php`)

Status column widened and other columns rebalanced so the longest label fits while the
row still totals 100%:

**Before:** `12% | 23% | 12% | 11% | 13% | 13% | 16%`

**After:** `18% | 21% | 12% | 11% | 13% | 13% | 12%`

```css
.pym-dash .pym-table th:nth-child(1), .pym-dash .pym-table td:nth-child(1) { width: 18%; overflow: visible; text-overflow: clip; }
.pym-dash .pym-table th:nth-child(2), .pym-dash .pym-table td:nth-child(2) { width: 21%; }
...
.pym-dash .pym-table th:nth-child(7), .pym-dash .pym-table td:nth-child(7) { width: 12%; }
```

The Status cell additionally overrides truncation as a defensive fallback:

```css
overflow: visible; text-overflow: clip;
```

#### Pill (`resources/views/payments/index.blade.php`)

Added `white-space: nowrap` to `.pym-pill` so the label never wraps:

**Before:**

```css
.pym-dash .pym-pill {
    display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
    border-radius: 7px; min-width: 40px; text-align: center;
}
```

**After:**

```css
.pym-dash .pym-pill {
    display: inline-block; font-size: 11px; font-weight: 700; line-height: 24px; padding: 0 10px;
    border-radius: 7px; min-width: 40px; text-align: center; white-space: nowrap;
}
```

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Status = "Awaiting Payment" | Pill clipped + "…" to its right | Full pill, no ellipsis |
| Status = short labels (Paid, Issued, …) | Fine | Fine (unchanged) |
| Status column width | 12% | 18% |
| Citation # column width | 23% | 21% |
| Actions column width | 16% | 12% |

### Impact & Risk `(required)`

- Affects: the `/payments` table layout only. Widths are percentages of a
  `min-width: 900px` fixed-layout table; at the 900px minimum the 18% Status column
  provides ~130px of content width, enough for the longest label, so the
  `overflow: visible` fallback does not overlap the adjacent Citation # cell.
- Risk: low. Cosmetic only; no controller, model, enum, data, or route changes.
- Vite rebuild not required (styles are scoped in the Blade `@push('styles')` block,
  not `app.css`).

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Status labels/enum values and `badgeClass()` mappings
- `PaymentController`, filters, sorting, pagination
- Other tables/views

### Known Issues / Follow-ups `(optional)`

- If a future status label is longer than "Awaiting Payment", the 18% width may need
  revisiting; the `overflow: visible; text-overflow: clip` override prevents an
  ellipsis but could overlap the next cell if the column becomes too narrow again.

### Testing / Verification `(required)`

- `php -l resources/views/payments/index.blade.php` — clean.
- `php artisan view:cache` → "Blade templates cached successfully"; then
  `php artisan view:clear`.
- Full suite: `php -d extension_dir="C:\php-8.3\ext" -d extension=mbstring -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit`
  → 14 tests / 34 assertions, all passed.