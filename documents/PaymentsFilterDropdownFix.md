# Fix Payments Filter Dropdown Panel Always Visible (Static)

### Date Edited / Applied `(required)`

- Edited and applied: 2026-10-13

### Type of Change `(required)`

- Bug fix

### Requested By / Source `(optional)`

- Reported by the repository owner while viewing http://127.0.0.1:8000/payments

### Problem `(required)`

- The Filters dropdown on /payments never hid or showed; the filter panel was rendered
  statically on the page instead of appearing/disappearing when the "Filters" button
  (`.pym-filter-button`) was clicked.
- Location: /payments → `resources/views/payments/index.blade.php` → `.pym-filter-panel`

### Root Cause `(required)`

- CSS cascade bug in the inline styles of `payments/index.blade.php`. The base rule
  `.pym-dash .pym-filter-panel` declared `display: none` and then re-declared
  `display: grid; gap: 22px;` later **inside the same rule block**. In CSS the last
  declaration wins, so the panel was permanently `display: grid`. Toggling the `.open`
  class via JavaScript had no visual effect because the base rule already forced the
  panel visible.

### Files Changed `(required)`

- `resources/views/payments/index.blade.php`

### What Parts Changed `(required)`

#### `resources/views/payments/index.blade.php`

**Before:**

```css
.pym-dash .pym-filter-panel {
    display: none; position: absolute; top: calc(100% + 8px); left: 0; z-index: 30;
    width: 421px; max-width: min(421px, 88vw); padding: 22px 18px 28px;
    background: #fff; border: 1px solid #dfe5eb; border-radius: 8px;
    box-shadow: 0 12px 32px rgba(13, 43, 72, .16), 0 2px 6px rgba(13, 43, 72, .08);
    display: grid; gap: 22px;          /* overrides the earlier display: none */
}
.pym-dash .pym-filter-panel.open { display: grid; }
```

**After:**

```css
.pym-dash .pym-filter-panel {
    display: none; position: absolute; top: calc(100% + 8px); left: 0; z-index: 30;
    width: 421px; max-width: min(421px, 88vw); padding: 22px 18px 28px;
    background: #fff; border: 1px solid #dfe5eb; border-radius: 8px;
    box-shadow: 0 12px 32px rgba(13, 43, 72, .16), 0 2px 6px rgba(13, 43, 72, .08);
}
.pym-dash .pym-filter-panel.open { display: grid; gap: 22px; }
```

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Load /payments with no filters | Filter panel visible statically on the page | Panel hidden until "Filters" is clicked |
| Click "Filters" | Panel state did not change | `.open` class added → panel appears |
| Click outside / press Escape | — | `.open` removed → panel hides |

### Impact & Risk `(required)`

- Affects: /payments for all roles with `viewAny` on Payment.
- Risk: low — the change only removes a duplicate/conflicting `display` declaration from
  the base rule and moves `gap: 22px` onto the `.open` rule, which is the JS toggle
  target. No JS or markup changed.

### Database / Migration Impact `(optional)`

- None

### Untouched `(optional)`

- Controller, model, routes, and the filter panel markup/JS in the same view
- Other pages (Citations drop-down uses a different `.cit-status-menu` rule and is unaffected)

### Known Issues / Follow-ups `(optional)`

- None

### Testing / Verification `(required)`

- `php artisan view:cache` → "Blade templates cached successfully"; then
  `php artisan view:clear`.
- Confirmed the fixed CSS: base rule has `display: none` only; `.open` is the sole
  `display: grid` source.
- Browser check at http://127.0.0.1:8000/payments: panel hidden on load, shows on
  "Filters" click, hides on outside click / Escape.