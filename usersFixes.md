# Users Page Fixes

## Fix 1 — Dropdown Menu Clipped Inside the Table

### File
`resources/views/users/index.blade.php`

### What Changed

#### CSS — stop `.table-responsive` from clipping the dropdown menu on laptop/desktop

**Before:** No page-specific style existed. The table was wrapped in Bootstrap's
`.table-responsive`, which sets `overflow-x: auto` (computed as `overflow-y: auto`
as well). This clipped the absolutely-positioned `.dropdown-menu` whenever the menu
was taller than the remaining space inside the container. The table row was the only
visible element around the cut-off menu, making it look "stuck inside" the
`table-warning` row.

```html
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            ...
            <tr class="table-warning">
                ...
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end"> ... </ul>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>
```

**After:** A page-scoped style block was added that lets the container stop
clipping at >= 992px:

```blade
@push('styles') //<- This was added
<style> //<- This was added
    @media (min-width: 992px) { //<- This was added
        .table-responsive { overflow: visible; } //<- This was added
    } //<- This was added
</style> //<- This was added
@endpush //<- This was added
```

### Why We Did It
When the Users list was filtered (e.g. `/users?search=john1%40gmail.com`), the
result set shrank to a few rows, so `.table-responsive` was only as tall as those
rows. The action dropdown (Approve / Reject / Edit / Devices) is taller than the
remaining space, and the container's computed `overflow: auto` cut off the menu. On
the full `/users` list the container is tall, so menus opened on middle rows stayed
inside its bounds and appeared fine.

### What Changed (Behavior)
- **Before:**
  - `/users` — the container was tall enough that dropdowns on middle rows stayed
    visible.
  - `/users?search=...` — with few rows, the container became short and the dropdown
    was clipped, appearing stuck inside the `table-warning` row.
- **After:**
  - Screens **>= 992px**: `.table-responsive` uses `overflow: visible`, so the
    dropdown menu extends past the table and Popper positions/flips it against the
    viewport — every action button is visible.
  - Screens **< 992px**: unchanged — `.table-responsive` keeps `overflow-x: auto`
    so the table can scroll horizontally on small screens.

### Effect on the System
- The style block is pushed only on the Users page, so no other tables are affected.
- Popper no longer treats the container as a clipping ancestor at >= 992px, so the
  menu flips relative to the viewport instead of being truncated.
- Mobile/tablet behavior is untouched.
