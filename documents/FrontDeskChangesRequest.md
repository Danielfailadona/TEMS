# Admin Dashboard Redesign (Change Request)

> Originally drafted as `FrontDeskChangesRequest.md`. After review the scope was
> changed to the **Admin dashboard only**; the FrontDesk dashboard is left
> untouched. This file keeps its original filename.

Change request for the Admin dashboard redesign. Follows the template in
`documents/documentation_Reference.md`.

> **STATUS: APPLIED (working tree, uncommitted).**
> Scope: `resources/views/dashboard/index.blade.php` (Admin/Cashier/Enforcer
> landing page) + `resources/css/app.css`. The FrontDesk view
> (`resources/views/dashboard/frontdesk.blade.php`) was explicitly **excluded**.
> Reference mockup: `testPages/dashboard-full-redesign.html` (27 Aug 2026).

---

## Admin Dashboard — Tiered Redesign

### Date Edited / Applied `(required)`

- Proposed: 2026-10-10
- Applied: 2026-10-10 (working tree; not yet committed)

### Type of Change `(required)`

- UI redesign (layout/markup only). No controller, route, model, policy,
  migration, or dependency changes.

### Requested By / Source `(optional)`

- Requested by user after comparing the live dashboards with the static mockup
  `testPages/dashboard-full-redesign.html`.
- FrontDesk was considered first, then explicitly excluded — only the Admin
  dashboard is changed.

### Problem `(required)`

- Location: `/dashboard` for admin-type roles (SuperAdmin, Administrator, and
  the Cashier / Enforcer variants) rendering
  `resources/views/dashboard/index.blade.php`.
- The page used plain Bootstrap KPI cards + a 2x2 chart grid + a bottom utility
  row and did not follow the mockup's tiered console layout
  (Needs attention → Pending work → Overview → Trends → Activity → Tools).
- The design system for that layout **already existed** in
  `resources/css/app.css` (`.section-label`, `.tier1-row`, `.tier3-row`,
  `.two-col`, `.queue-section`/`.queue-row`/`.queue-count`/`.queue-btn`,
  `.stat-card.state-*`/`.state-dot`/`.stat-value`/`.stat-trend`,
  `.stat-card--quiet`, `.panel`, `.chart-empty`/`.chart-caption`,
  `.stat-with-caption`, `.qa-btn`) but was used by 0 views.

### Root Cause `(required)`

- The CSS layer for the tiered design shipped earlier (commit `968f1d4`) but the
  dashboard view was never rewritten to consume it. The mockup is the missing
  markup reference.

### Files Changed `(required)`

- `resources/views/dashboard/index.blade.php` (Admin dashboard markup)
- `resources/css/app.css` (backgrounds, quiet-card flex, responsive stacking)

### What Parts Changed `(required)`

#### `resources/views/dashboard/index.blade.php`

New tiered structure (all existing role/`@can` gates preserved):

| Section | Markup | Data |
|---|---|---|
| Header | unchanged | `$greeting` |
| **Needs attention** | `.section-label` + `.tier1-row` → 3 `.stat-card.state-*` (count-based 3-state: 0 = green, `<red` = amber, `>=red` = red; cutoffs 4 / 10 / 50) | Active Clamps · Pending Appeals · Unpaid Citations |
| **Pending work** | `.section-label` + `.queue-section` → `.queue-row`/`.queue-count.zero|.some`/`.queue-btn` | `$pendingQueue` appeals / clamping_requests / account_approvals |
| Enforcer GPS | unchanged (Enforcer only) | — |
| **Overview** | `.section-label` + `.tier3-row` → 2 `.stat-card--quiet` | Total Citations · Payments Today |
| **Trends** | `.section-label` + two `.two-col` rows of `.panel` | Citations / Payments / Top Violations / Appeals |
| **Activity** | `.section-label` + `.panel` | `$recentActivity` (not Enforcer/Cashier) |
| **Tools** | `.section-label` + `.two-col` | Quick Actions (`.qa-btn`) + Zone map |

Trend panels have server-side empty-state guards:
- ≥2 points → Chart.js canvas (`#citationsChart`, `#revenueChart`, `#appealsChart`)
- 1 point → `.stat-with-caption`
- 0 points → `.chart-empty` + `.msg`

Top Violations keeps its list; empty state now uses `.chart-empty`.
The `@push('styles')` block dropped the unused `.pending-card`/`.pending-count`.

#### `resources/css/app.css`

- `.stat-card`, `.queue-section`, `.panel` given `background:#fff` (the design
  system previously relied on Bootstrap `.card` for the background; the new
  markup uses these classes standalone).
- `.stat-card--quiet` given `display:flex; align-items:center;
  justify-content:space-between` to match the mockup.
- `.two-col > .panel` added to the `flex: 1 1 0; min-width: 0` child rule.
- Responsive: in the existing `@media (max-width: 768px)` block, `.tier1-row`,
  `.tier3-row`, `.two-col` stack to one column (`flex-direction: column` +
  `align-items: stretch`).
- **Mockup-parity pass** (the design system was incomplete vs
  `dashboard-full-redesign.html`, so components rendered unstyled):
  - `.panel` given `padding:16px 18px` (was missing) + `.panel h3`
    (`margin:0 0 12px; font-size:15px`).
  - `.queue-btn` given real button treatment: `border:1px solid
    var(--itevcms-primary)`, matching color, transparent background, `6px 14px`
    padding, `0.8125rem` font, plus a hover fill.
  - `.section-label` given `font-weight:700` and `margin:18px 0 8px 2px`
    (`:first-of-type { margin-top:0 }`).
  - `.two-col` given `align-items:flex-start` (mockup prevents panels stretching
    to the tallest sibling).
  - `.qa-btn` matched to mockup: `width:100%`, `background:#fff`, 1px border,
    `12px 14px` padding, `14px/600` font, `margin-bottom:10px`, and the lighter
    mockup variant border colors (blue/green/red/gray).
  - Tier gaps set to mockup values: `.tier1-row` `16px`, `.tier3-row` `12px`,
    `.two-col` `14px`.
- Not touched: the duplicate `.stat-card` / `.queue-section` definitions.

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Admin dashboard layout | flat 5 KPI cards + 2x2 charts + 1 utility row | tiered sections with labels |
| Top row | 5 equal KPI cards | 3 "Needs attention" state cards (green/amber) |
| Actionable queue | lower utility card | promoted full-width "Pending work" section |
| Secondary metrics | in top KPI row | demoted "Overview" quiet cards |
| Empty charts | blank canvas | explicit empty-state caption |
| Mobile | KPI grid reflows | tier rows stack ≤768px |
| Backend / data | — | unchanged |

### Impact & Risk `(required)`

- Affects: `/dashboard` for admin-type roles. FrontDesk unaffected.
- Risk: low-to-medium — view-only, but the dashboard is high-traffic and
  role-dependent. All `isRole` / `@can` gates, Chart.js init, GPS JS and the
  zone map are preserved; a regression would affect every admin landing page.
- Mitigation: markup-only; verify Admin, Cashier and Enforcer each render.

### Database / Migration Impact `(optional)`

- None.

### Untouched `(optional)`

- `DashboardController` (view selection + all data keys), routes, models,
  policies, enums, all `@push('scripts')` JS, `$recentActivity`/`$zoneMapData`,
  GPS + zone-picker wiring, `composer.*`, config, `render.*`.
- `resources/views/dashboard/frontdesk.blade.php` (excluded by decision).

### Known Issues / Follow-ups `(optional)`

- `.qa-row` in app.css is a single-column grid; the two-column Tools layout is
  achieved with the `.two-col` wrapper (matches plan).
- Blade **can** be syntax-checked locally with `php artisan view:cache` (then
  `php artisan view:clear`). Only the `php -r "extension_loaded('mbstring')"` probe
  reports missing — the artisan view compiler still succeeds.
- Hard refresh (Ctrl+F5) needed to see the new compiled CSS after build.
- Duplicate `.stat-card` / `.queue-section` definitions in app.css still exist
  (pre-existing; out of scope).
- The "Needs attention" colour thresholds and the researched RAG/SLA/accessibility
  guidance (what the internet recommends) are documented in
  `documents/AdminUI.md` — see the 2026-10-11 section, **Appendix E** (future
  SLA-based RAG) and **Appendix F** (research notes with sources).

### Testing / Verification `(required)`

- Conflict-marker scan: none.
- Tag balance: `<div>` open = 100, `</div>` close = 100.
- Directive balance: `@if`/`@endif` 14/14, `@foreach`/`@endforeach` 2/2,
  `@forelse`/`@endforelse` 1/1, `@php`/`@endphp` 4/4, `@can`/`@endcan` 4/4,
  `@push`/`@endpush` 2/2.
- Gate counts vs original: `@can` 4 → 4; `isRole` 6 → 7 (one added: the Pending
  work section has its own `!Cashier` gate, previously folded into the single
  bottom-row `!Cashier` block). No gates dropped.
- Data keys confirmed against `DashboardController::index()`.
- Needs attention colour rule upgraded to count-based 3-state RAG (see
  `documents/AdminUI.md`, section 2026-10-11); `.state-red`/`.state-dot` already
  present in the built bundle, so no rebuild needed for this part.
- Pending: per-role render check.

---

## Appendix A — Appeals: who can act

| Capability | Route / method | Allowed roles |
|---|---|---|
| See appeals list | `appeals.index` (`viewAny`) | all roles |
| View one appeal | `appeals.show` (`view`) | any staff; VehicleOwner own only |
| Create appeal | `appeals.create`/`store` | VehicleOwner only |
| Review / decide | `appeals.edit`/`update` (`update`) | SuperAdmin, Administrator, Enforcer only |

## Appendix B — FrontDesk capabilities (context; unchanged)

FrontDesk's only actionable work is processing releases (`WaitingRelease`);
appeals are admin-only to decide. **FrontDesk is not changed by this request.**
