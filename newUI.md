# New UI — Dashboards & Views Adopted from `master`

Documents the `origin/master` (`e6e3192`) → `design-v1.1` merge and, specifically, **which master-built dashboard/UI we adopted wholesale versus which we hand-merged into the local redesign**.

Merge is staged but **not committed**. `master` was never modified.

---

## Summary

| Area | Decision | Result |
|---|---|---|
| `payments/index` | Took master's whole view | Two-mode card UI (Receipts / Awaiting Payment) |
| `dashboard/index` | Took master's whole view | Chart.js charts, Payments Trend, Top Violations, Pending Work Queue |
| `clamping-requests/index` | Took master's whole view | Stat cards + OpenStreetMap embeds |
| `clamping/index` | Took master's whole view | Mark Paid / Waiting / Release workflow actions |
| `impounding/index` | **Hand-merge** | Local table design + Evidence column + 2 workflow buttons |
| `citations/index` | **Hand-merge** | Local table design + Evidence column |
| `audit-logs/index` | Kept local | Local filter toolbar was already a superset |
| `users/*` | Kept local + master's include | Preserved AJAX `#users-card` contract |
| `impounding/show` | Took master's | Local version referenced an undefined `$clamping` |
| `app.css` | **Union** | Kept both sides' non-overlapping rules |

Controllers resolved by taking master's: `PaymentController`, `ClampingController`, `ImpoundingController`.

---

## Why master's UI won on the four dashboards

Three independent facts made master's markup the correct choice rather than a stylistic preference:

1. **Controller/view variable contracts.** The auto-merged controllers pass variables only master's views read. Keeping our view would fatal on undefined variables:
   - `PaymentController@index` always returns `payments.index` with `$viewMode` (`receipts` → `viewMode`+`payments`; `payables` → `payableCategory`+`payableCounts`+`payables`). Our table view has no `$viewMode` awareness → `?view=payables` would fatal on undefined `$payments`, making the whole Awaiting Payment feature unreachable.
   - `DashboardController` passes `$citationsByMonth`, `$revenueByMonth`, `$topViolations`, `$pendingQueue`.
   - `ClampingRequestController@index` returns `compact('requests', 'stats')`.

2. **Orphaned activation code.** `dashboard/index` lines 633-736 already contained master's Chart.js init in a **non-conflict region**, targeting `#citationsChart`, `#revenueChart`, `#appealsChart`. Keeping our SVG-only view left that code hunting canvases that never rendered.

3. **Superseded inline CSS.** Each page's design CSS lived in an inline `<style>` block inside the view itself, so adopting master's view removed that page's `pay-*` / `clp-*` CSS along with its markup. No orphaned CSS remains.

---

## `payments/index` — Two-mode payments

Master's rewrite, replacing the local `pay-dash` table (`pay-table-card`, `pay-entry-count`, `pay-footer`).

**Receipts / Awaiting Payment toggle** — `nav nav-pills`:
- Receipts preserves `search`, `payment_method`, `category`, `date_from`, `date_to`, `online` via `array_filter(request()->only([...]))`
- Awaiting Payment → `['view' => 'payables']`

**Awaiting Payment mode:**
- Category pills with live outstanding counts from `$payableCounts['citation'|'clamping'|'impounding']`
- Cards built from `$payables`; amount resolved by `match ($payableCategory)`: `clamping_fee`, `getTotalFees()`, or `penalty_amount`
- Date shown via `($item->issued_at ?? $item->clamped_at ?? $item->impounded_at)`
- Collect button deep-links to `payments.create` with `citation_id` / `clamping_id` / `impounding_id`

**Receipts mode:** search, category, date range, online-only filters; `payment-card` grid.

Model methods master's view depends on (all auto-merged into `app/Models/Payment.php`): `category()`, `categoryLabel()`, `payableNoticeNumber()`, `payableVehicle()`, `payablePerson()`, `isOnlinePayment()`, `getStatusBadgeClass()`, `getStatusLabel()`.

**Controller filters adopted** (`PaymentController@index`) — a strict superset of local: polymorphic search across clamping/impounding notices via `orWhereHasMorph`, category/payment-method/date-range filters, `online` filter, `paginate(6)` instead of 5.

> Note: master drops local `->whereNotNull('paid_at')`. This is intentional — it is what makes pending payments visible in the Awaiting Payment queue.

---

## `dashboard/index` — Chart.js + work queues

Master's Chart.js version, replacing five hand-rolled inline SVG `mini-chart` sparklines.

- **Citations Trend** → `#citationsChart`
- **Payments Trend** → `#revenueChart`, hidden from `Role::Enforcer`
- **Appeals** → `#appealsChart`
- **Top Violation Types** — horizontal bars from `$topViolations`, scaled against `max('count')`, "No data for the last 3 months" empty state
- **Pending Work Queue** — hidden from `Role::Cashier`; `$pendingQueue['appeals'|'clamping_requests'|'account_approvals']`, each linking out (`appeals.index`, `clamping-requests.index`, `users.index`). Account Approvals hidden from Enforcers.
- Grid moved from `col-xl-6` (2-up) to `col-xl-4` (3-up)

**Chart.js loading is resilient, not a hard CDN dependency.** The init block loads from `cdn.jsdelivr.net/npm/chart.js@4.4.1`, and on failure falls back to injecting the script tag, then degrades gracefully:

```js
console.warn('Chart.js failed to load, charts disabled');
```

`package.json` also declares `chart.js: ^4.5.1` (imported in `resources/js/app.js`), so a bundle path exists. The same pattern is used in `dashboard/frontdesk`, `dashboard/revenue`, and `citizen/citation-lookup`.

---

## `clamping-requests/index` — Stats + map embeds

Master's card grid, replacing the local `cr-` table (`cr-table-card`, `cr-badge`, `cr-view-btn`).

- **Five stat cards** from `$stats`: Total, Pending, Approved, Resolved, Rejected — `row-cols-xl-5`
- Status filter chips (All / Pending / Approved / Rejected / Resolved) preserving `search`
- Cards show plate, requester, location, assigned officer, date, contact phone
- **Clamp Notice link** when `$r->clampingRecord` exists
- **OpenStreetMap iframe** per card when `latitude`/`longitude` present — lazy-loaded, `marker={lat},{lng}`, bbox padded ±0.001/±0.002
- Show Map / Hide Map toggle; opening one panel closes all others (`.map-toggle` / `.map-panel` / `.map-label`)

> The map classes are styled by **inline `style=""` attributes in the view**, not in `app.css` — verified, so no missing stylesheet.

---

## `clamping/index` — Workflow actions

Master's card grid, replacing the local `clp-` table (`clp-table-card`, `clp-badge`, `clp-view-btn`).

- Page header with **Record Clamp** button
- **Eligible Vehicles** — now a `collapse show` card with a count badge, collapsed by default, and a **Driver** column (local version had an empty `<th>`)
- **Status + date-range filters** added (`status`, `date_from`, `date_to`) plus Reset — local had search only
- Records show officer, clamped-at, location, citation, clamping fee, paid-at, released-at
- **Inline workflow forms**, each with a `confirm()` guard:
  - **Mark Paid** → `clamping.mark-paid` (hidden `clamping_fee`, `payment_method=cash`)
  - **Waiting** → `clamping.mark-waiting-release`
  - **Release** → `clamping.process-release`
  - All three wrapped in `@can` policy checks

**Controller adopted:** `ClampingController@index` gained `status`/`date_from`/`date_to` filters and `paginate(12)` (local: 5).

> **Known trade-off:** local's Citizen Clamping Requests table (`$pendingRequests`) is not in master's version. That data remains reachable via the `clamping-requests` page, which the dashboard's Pending Work Queue links to.

---

## `impounding/index` — Hand-merge

The conflict was **structural**: local contributed `<th>` header cells while master contributed `<td>` body cells. Taking either side wholesale would break the table — master's side would place `<td>`s inside `<thead><tr>`.

Resolved by keeping local's valid `<thead>` and porting master's additions:

- Added `<th scope="col">Evidence</th>` → 8 columns
- Evidence `<td>` renders a 36×36 thumbnail via `SupabaseStorage::publicUrl()`, or an `imp-muted-dash` fallback
- Added **Queue Release** (`@can markWaitingRelease`, plain POST form) and **Process Release** (`@can processRelease`, modal) to `.imp-actions`
- `colspan` bumped 7 → 8

> **Extra work required:** master's `releaseModal-{{ $record->id }}` markup was **not** in the merged file (git took our deletion). Adding the Process Release button without it would target a missing modal, so the modal was re-inserted inside the existing `@foreach`, wrapped in `@can('processRelease')`.

---

## `citations/index` — Hand-merge

Same shape as `impounding/index`: master added an Evidence column, local's `<thead>` was the only valid one.

- `<th scope="col">Evidence</th>` added before Actions
- Evidence `<td>` renders a Yes/No badge from `$citation->evidence->isNotEmpty()` (the `evidence()` HasMany → `CitationEvidence` relation exists on our branch too)
- `colspan` bumped 7 → 8
- Local `cit-pill` status badge and `cit-view-btn` icon button preserved

---

## `audit-logs/index` — Kept local

Git mis-aligned this one, inserting master's Bootstrap filter card *inside* local's `al-toolbar`. Taking master would have produced two nested `<form>`s with duplicate inputs.

Local is already a **functional superset**, so it was kept:
- All five filters local and master both support: `log_name`, `event`, `date_from`, `date_to`, `search`
- Local adds the filter overlay popup, per-filter **active chips** with individual clear links, an Apply Filters button, and a styled date-range row

`AuditLogController` supports all filters plus `causer_id`.

---

## `users/` — Kept local structure, adopted master's partial

**`users/index.blade.php`** — union of two attributes on the role filter:
```html
<select name="role" class="form-select" aria-label="Filter by role" onchange="this.form.submit()">
```

**`users/partials/users-card.blade.php`** — kept local's `<div id="users-card">` wrapper, swapped local's inline toolbar for `@include('users.partials.batch-toolbar')`.

**The wrapper is load-bearing.** `UserController@index` returns `users.partials.users-card` for AJAX, and the swap logic depends on it:
```js
const tableCard = document.getElementById('users-card');
const newCard = doc.getElementById('users-card');
if (newCard) tableCard.innerHTML = newCard.innerHTML;
```
Dropping the wrapper makes `newCard` null and silently kills filter/pagination refresh. Master's partial is a clean extraction of the same toolbar (identical element IDs, restyled, and it fixes local's duplicate `data-bs-toggle` attribute on the reject button).

---

## `impounding/show` — Took master's

Local's side referenced `$clamping->citation->…`, but `ImpoundingController@show` passes `compact('impounding')` — **`$clamping` does not exist**, so local's block was a fatal `Undefined variable`. Master also correctly uses `$impounding->impounded_at`.

---

## `resources/css/app.css` — Union

Both sides were additive and non-overlapping:
- **Kept local:** `.enforcer-more-item form.enforcer-more-item { display: contents; }`, the full `.enforcer-more-btn` block, and `.tag-*` status color coding (green/blue/red/amber/gray + shared radius/padding/weight)
- **Added master's:** `@media (min-width: 1600px)` ultra-wide container rule and `.form-shell { max-width: 1440px; margin-inline: auto; }`
- Master's condensed `.enforcer-more-btn i` / `:hover` rules were byte-identical to local's — kept local's multi-line form, no duplication

Brace balance verified: 177 `{` / 177 `}`.

---

## `docker/start-container.sh` — Union

Not a UI change, but part of the same merge. Resolved as a clean union — master's new command plus local's fail-loud behavior:
```sh
php /var/www/html/artisan supabase:init 2>/dev/null || true
php /var/www/html/artisan migrate --force --no-interaction
```
Local's `${PORT:-80}` nginx rewrite sits outside the conflict and is untouched.

---

## Shared conventions in the adopted views

- **`.stat-card`** — defined in `app.css` (27 references), used 12× in the two adopted list views
- **`.min-width-0`** — Bootstrap 5.3.8 utility, no local CSS needed
- **`.map-toggle` / `.map-panel` / `.map-label`** — inline `style=""` attributes in the view
- **`.payment-card` / `.payment-status-badge` / `.filter-panel`** — inline `<style>` in `payments/index`
- **`.pending-card` / `.pending-count` / `.chart-box` / `.violation-item` / `.violation-bar`** — inline `<style>` in `dashboard/index`
- **`.chart-box`** — `height: 200px`; `.chart-box-sm` — `150px`

---

## Verification performed

| Check | Result |
|---|---|
| Unmerged files | none (15/15 resolved) |
| Conflict markers in tracked files | none |
| `php -l` on changed controllers + services | pass |
| `bash -n docker/start-container.sh` | pass |
| `app.css` brace balance | 177 / 177 |
| Blade directive balance (`@if`/`@endif`, `@foreach`, `@forelse`, `@can`) | balanced in every hand-merged file |
| `<div>` / `</div>` balance in adopted views | balanced |
| BOM / line endings on rewritten views | none (LF, byte-exact from master) |
| `render.env` staged? | no |

> `@section('title', 'Payments')` compiles to `addSection` and needs no `@endsection`; only `@section('content')` does. So one `@endsection` for two `@section(` calls is correct, not an imbalance.

---

## Open items (not part of the UI merge)

1. **`app/Services/SupabaseStorage.php` is misconfigured for Render.** Master-only, and it bypasses Flyystem entirely — it calls Supabase's REST API directly (`{SUPABASE_URL}/storage/v1/object/public/{bucket}/{path}`) with the **service-role key**. It reads `SUPABASE_URL`, `SUPABASE_SERVICE_ROLE_KEY`, `STORAGE_BUCKET` (`config/supabase.php`), **none of which are in `render.yaml` / `render.env`.** Consequence: `publicUrl()` silently falls back to `asset('storage/'.$path)` → broken evidence images, and `put()` throws. This affects **21 call sites across 16 views** (citations, citizen portal, clamping, profile, tickets, impounding).
2. **Backup file not yet created.** Pending confirmation of what the "local save" should contain.
3. **5 new migrations** from master will run under `migrate --force` on deploy, including `convert_clamping_officer_role_to_enforcer`, which is a role/data migration worth reviewing first.
4. Merge is **staged, not committed** — awaiting approval.
