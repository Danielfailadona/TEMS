# Payments Filter Not Applying ("Apply Filter" does nothing) Fix

### Date Edited / Applied `(required)`

- Edited and applied: 2026-10-19

### Type of Change `(required)`

- Bug fix (JavaScript error aborted script setup, so the Apply Filter / search handlers were never bound)

### Requested By / Source `(optional)`

- Reported by the repository owner on `/payments`: clicking "Filters" opened the
  panel, but clicking "Apply Filter" did nothing — the page never navigated, so the
  filters looked like static designs "not connecting to the backend".

### Problem `(required)`

- On `/payments`, the "Filters" button worked (the panel opened and closed), but the
  "Apply Filter" button had no effect at all — no page reload and no URL change, so
  no filter was ever sent to the server. The search debounce was likewise inert
  (search still worked only because its button is a native `type="submit"`).
- Location: `/payments` → `resources/views/payments/index.blade.php`
  → the `@push('scripts')` block.

### Root Cause `(required)`

- The `DOMContentLoaded` handler attached listeners in this order:
  1. Filters toggle (`#pymFilterBtn`) — lines 477–487.
  2. Date-picker loop (`[data-date-picker]`) — lines 503–518.
  3. Apply Filter (`#pymApplyBtn`) — line 520.
  4. Search debounce (`#pymSearchInput`) — line 542.
- In the date-picker loop the native input was looked up with
  `document.getElementById(btn.dataset.nativeFor)`. But `data-native-for` was **never
  placed on the picker buttons** — it was mistakenly put on the native `<input>`
  elements instead (pointing at the text inputs, which the JS never reads).
- Therefore `btn.dataset.nativeFor` was `undefined`, `getElementById(undefined)`
  returned `null`, and the next line `native.addEventListener('change', …)` threw
  `TypeError: Cannot read properties of null (reading 'addEventListener')`.
- That exception aborted the rest of the `DOMContentLoaded` callback **before** the
  Apply Filter handler (line 520) and the search handler (line 542) were attached.
  The Filters toggle (line 477) had already been attached, which is why only it kept
  working — matching the reported symptom exactly.

### Files Changed `(required)`

- `resources/views/payments/index.blade.php`
- `documents/PaymentsFilterNotApplyingFix.md` (this log)

### What Parts Changed `(required)`

#### Date-picker script (`resources/views/payments/index.blade.php`)

Replaced the single fragile attribute lookup with a hybrid lookup (explicit
attribute first, DOM-neighbour fallback) plus a guard so one missing element can no
longer abort the whole setup callback:

**Before:**

```js
form.querySelectorAll('[data-date-picker]').forEach((btn) => {
    const text = document.getElementById(btn.dataset.datePicker);
    const native = document.getElementById(btn.dataset.nativeFor);
    btn.addEventListener('click', () => {
        ...
    });
    native.addEventListener('change', () => {   // TypeError: native is null
        ...
    });
});
```

**After:**

```js
form.querySelectorAll('[data-date-picker]').forEach((btn) => {
    const wrap = btn.closest('.pym-input-wrap');
    const text = document.getElementById(btn.dataset.datePicker) || wrap?.querySelector('.pym-input');
    const native = document.getElementById(btn.dataset.nativeFor) || wrap?.querySelector('.pym-date-native');
    if (!text || !native) return;               // skip only this picker; never crash the rest
    btn.addEventListener('click', () => {
        ...
    });
    native.addEventListener('change', () => {
        ...
    });
});
```

#### Picker buttons (`resources/views/payments/index.blade.php`)

Added the missing `data-native-for` attribute so the explicit (arrangement-proof)
lookup resolves to the correct native date input:

**Before:**

```html
<button class="pym-input-wrap__btn" type="button" data-date-picker="pymFromText" aria-label="Pick start date">
<button class="pym-input-wrap__btn" type="button" data-date-picker="pymToText"   aria-label="Pick end date">
```

**After:**

```html
<button class="pym-input-wrap__btn" type="button" data-date-picker="pymFromText" data-native-for="pymFromNative" aria-label="Pick start date">
<button class="pym-input-wrap__btn" type="button" data-date-picker="pymToText"   data-native-for="pymToNative"   aria-label="Pick end date">
```

#### Native date inputs (`resources/views/payments/index.blade.php`)

Removed the stray, misleading `data-native-for` attributes the JS never read (they
pointed at the text-input ids, not the native input ids):

**Before:**

```html
<input class="pym-date-native" type="date" name="date_from" id="pymFromNative" tabindex="-1" aria-hidden="true" data-native-for="pymFromText" value="{{ $fromYmd }}">
<input class="pym-date-native" type="date" name="date_to"   id="pymToNative"   tabindex="-1" aria-hidden="true" data-native-for="pymToText"   value="{{ $toYmd }}">
```

**After:**

```html
<input class="pym-date-native" type="date" name="date_from" id="pymFromNative" tabindex="-1" aria-hidden="true" value="{{ $fromYmd }}">
<input class="pym-date-native" type="date" name="date_to"   id="pymToNative"   tabindex="-1" aria-hidden="true" value="{{ $toYmd }}">
```

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Click "Filters" | Panel opens/closes | Panel opens/closes (unchanged) |
| Pick a Method + click "Apply Filter" | Nothing at all; no navigation, no URL change | Navigates to `/payments?payment_method=…&…`; server filters the grid |
| Click a calendar icon | Handler present but crashed on `native` (null) | Native date picker opens; picking a date fills the text field |
| Date validation error (bad `dd/mm/yyyy`) | Unreachable | Inline error shown via `#pymDateError` |
| Type in search box | No debounce auto-submit | Auto-submits after 300 ms |
| Browser console | `TypeError: Cannot read properties of null (reading 'addEventListener')` | No error |

### Impact & Risk `(required)`

- Affects: the `/payments` toolbar only — Filters panel, date pickers, Apply Filter,
  and search debounce.
- Risk: low. No controller, model, enum, route, validation, query, sort, or
  pagination logic changed; the filter query string and server-side filtering were
  already correct.
- The guard is the key hardening: any future null (e.g. a renamed/moved element) now
  skips only that picker instead of disabling Apply Filter and search.
- Vite rebuild not required (the script lives in the Blade `@push('scripts')` block,
  not in `app.js`).

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- `PaymentController@index` / `pendingRows()` filter logic
- Filter field names (`payment_method`, `category`, `date_from`, `date_to`, `online`,
  `search`) and the grid/pagination/sorting
- `resources/js/app.js`, CSS/`@push('styles')`

### Known Issues / Follow-ups `(optional)`

- Browsers/`php artisan serve` may serve the previously compiled Blade view; clear it
  (`php artisan view:clear`) and hard-reload (Ctrl+F5) to see the fix.
- The calendar icon remains a convenience only: dates can always be typed into the
  text fields and submitted, even if a picker lookup is ever skipped.

### Testing / Verification `(required)`

- `php -l resources/views/payments/index.blade.php` — clean.
- `php artisan view:cache` → "Blade templates cached successfully"; then
  `php artisan view:clear`.
- Full suite: `& "C:\php-8.3\php.exe" "vendor/phpunit/phpunit/phpunit"` — 14 tests /
  34 assertions, all passed.
- Manual: on `/payments`, open Filters → pick a Method → Apply Filter → URL becomes
  `/payments?payment_method=…` and the grid filters; console has no `TypeError`.
