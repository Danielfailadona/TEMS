# TEMS — New Requirements Implementation Plan (Fetch-Only)

> Status: **P0, P1, P2, P3, P4, P5, P6, P8, P9, P10, and P11 COMPLETE.** Remaining phase: P7 (Column Deduplication) — intentionally last.
> Guiding principle: **Fetch, don't duplicate** — minimise stored data to cut storage use.

---

## 0. Fetch-Only Doctrine (applies to every phase)

1. **One source of truth.** `citations` owns vehicle/driver data; `payments` owns payment
   facts; `users` owns identity. Nothing re-stores them.
2. **Reference, don't copy.** New rows store FKs/IDs + only their intrinsic data; display
   pulls through relations/accessors.
3. **Snapshot only at true deletion.** Historical JSON is written only when a record is
   actually destroyed — never on routine status changes.
4. **Log the minimum.** Audit/activity logs capture only audit-critical fields.
5. **Evidence = references.** Store the storage path once; other records point to it.

Each phase verified with `php -l`, `php artisan view:clear|view:cache|view:clear`,
`php artisan test`. No PHP edits via PowerShell `-replace`.

---

## 1. Requirement → Phase Map

| # | Requirement | Phase |
|---|-------------|-------|
| 1 | Citizen pages responsive (desktop→mobile) | P10 |
| 2 | Proper roles; FrontDesk = appeal sender; remove citizen appeals | P1, P5 |
| 3 | Admin "Clamping Request" → "Citizen Report"; report ticket | P2, P3 |
| 4 | Admin Task Assignment page under Operations | P3 |
| 5 | Citation violation "Others" + manual input | P6 |
| 6 | Citizen report status "Approved" → "Accepted" | P2 |
| 7 | Enforcer "Clamping Request" → "Citizen Reports"; accept/reject | P4 |
| 8 | Auto-assign by availability (random among available) | P3 |
| 9 | Enforcer: Issue Citation (modal) / Resolve with Notes | P4 |
| 10 | Fetch citizen report → citation form (no redundancy) | P4 |
| 11 | Citizen report add make/model/color | P2 |
| 12 | Cashier = recording + print via archives + per-user KPI | P8 |
| 13 | Standalone per-user KPI cards | P8 |
| 14 | Cashier unpaid-citation-by-overdue report | P8 |
| 15 | Citizen lookup autofill payment form from citation | P9 |
| 16 | Citizen report "Full name" field | P2 |
| 17 | Citizen report → "Under Review" ticket (not auto-citation) | P2 |
| 18 | FrontDesk sends appeal; shows approved/rejected + notes | P5 |
| 19 | FrontDesk view tile → appeal modal with reason note | P5 |
| 20 | Data redundancy / storage audit | **P0** (done) + P7 |

---

## 2. Confirmed Decisions

- **D1 Report storage:** rename `clamping_requests` → `citizen_reports` (+ `CitizenReport`).
- **D2 Report ticket:** `report_number` prefix `RPT-`; unified from `reference_number`.
- **D3 Report statuses:** `pending` → `accepted` → `resolved`; plus `rejected`.
- **D4 Availability:** manual `users.is_available` AND online (active GPS); random assign.
- **D5 Appeals:** FrontDesk submits; citizen/guest submission removed; display ticket #.
- **D6 "Others":** free-text `citations.custom_violation_name` + penalty on the citation.
- **D7 KPI role-scoped** + "My Contribution" block.
- Reports never auto-create citations; only enforcer action does.

---

## 3. Deep Redundancy Audit (P0 findings)

| ID | Redundant data | Source of truth | Duplicated in | Impact | Fix | Status |
|----|----------------|-----------------|---------------|--------|-----|--------|
| R1 | vehicle plate | `citations` | `clamping_records.vehicle_plate` | per-row | accessor → citation; drop | P7 |
| R2 | vehicle plate | `citations` | `impounding_records.vehicle_plate` | per-row | accessor → citation; drop | P7 |
| R3 | payment facts | `payments` | `clamping_records.{clamping_fee,payment_method,reference_number,paid_at}` | per-row | read relation; drop | P7 |
| R4 | whole record | the record | `archives.snapshot` (15 sites) | **HIGH** | **fetch live; snapshot only on delete** | **P0 DONE** |
| R5 | all attrs | the model | `activity_log.properties` `logAll()` | **HIGH** | `logOnly([...])` | **P0 DONE** |
| R6 | submitter identity | `users` | `appeals.guest_name/guest_contact` | per-row | remove | **P5 DONE** |
| R7 | vehicle spec | report | `clamping_requests.vehicle_description` | per-row | structured make/model/color | P2 |
| R8 | evidence blob | storage | clamping/impounding `evidence_path` | HIGH if copied | reference path (already reused) | P0 verified |
| R9 | policy fees | `config` | impounding fee columns | point-in-time | keep (history) | — |
| R10 | IDs | records | `notifications.data` | Low | keep IDs only | P0 verified |
| R11 | citation link | — | `payments.citation_id` + `payable_*` | Low | standardise | P7 |
| R13 | ref number | report | `clamping_requests.reference_number` | Low | unify | P2 |
| R14 | session/log/failed | — | `sessions`,`activity_log`,`failed_jobs` | unbounded | prune command | **P0 DONE** |

---

## 4. Phase Status

| Phase | Title | Status | Notes |
|-------|-------|--------|-------|
| **P0** | Storage & Redundancy Foundation | ✅ **DONE** | archives fetch-live, activity-log trim, prune command |
| **P1** | Roles & Permissions Foundation | ✅ **DONE** | role helpers, `delete()` abilities + UserPolicy, SuperAdmin-only deletes (users/teams/zones), gated delete UI |
| **P2** | Citizen Report System | ✅ **DONE** | renamed → `citizen_reports`, structured make/model/color, `RPT-` ticket, Under Review→Accepted→Resolved, no auto-citation |
| P3 | Admin Report Mgmt + Task Assignment + Auto-assign | ✅ **DONE** | `operations/task-assignment` page, `users.is_available`, `AutoAssignService` (available AND online), auto-assign on accept |
| P4 | Enforcer Citizen Reports (accept/reject/resolve) | ✅ **DONE** | enforcer claim/accept/reject, Issue Citation modal (fetch report → citation, links `citation_id`), Resolve with Notes |
| P5 | FrontDesk Appeals | ✅ **DONE** | citizen appeals removed; FrontDesk files; ticket #; two-column show (summary + full ticket) |
| P6 | Citation "Others" Violation | ✅ **DONE** | `citations.custom_violation_name` + free-text penalty; `violationLabel()`; seeded `others` type |
| P7 | Column Deduplication | ⬜ Pending | drop R1/R2/R3 after readers are fetch-based |
| P8 | Cashier Scope + Per-User KPI + Overdue Report | ✅ **DONE** | dedicated Cashier dashboard (record + print via archives), `UserKpiService` "My Contribution", `reports.overdue-citations` report |
| P9 | Lookup Autofill | ✅ **DONE** | citizen pay form autofills payer name/amount from citation; `public.citation.pay` + `citizen/citation-pay.blade.php` |
| P10 | Responsive Design | ✅ **DONE** | table-responsive on all reports; citizen views use responsive grids; CSS media queries for dashboard/navbar |
| P11 | Tests & Docs | ✅ **DONE** | full suite 39 tests / 111 assertions; plan doc updated |

---

## 5. P0 — Completed Work (detail)

- **`app/Models/Archive.php`**: added `Archive::log($model,$reason,$by,$snapshot=null)` and
  `resolvedSnapshot()` (fetches the live record + relations when snapshot is null).
- **15 archive call sites** replaced `Archive::create([... 'snapshot' => $x->toArray()])`
  with `Archive::log(...)` — `PaymentRecorder` (3), `CitationController` (3),
  `ClampingController` (3), `ImpoundingController` (2), `AppealController` (1),
  `ClampingRequestController` (1), `CitizenPortalController` (1), `PayMongoController` (1).
- **Render sites** now fetch live: `archives/index.blade.php`, `archives/print.blade.php`,
  `ArchivesExport.php`, `ArchiveController@backup`; queries eager-load `archivable`.
- **Activity logs trimmed** (`logAll()` → `logOnly([...])`): `Citation`, `ClampingRecord`,
  `ImpoundingRecord`, `Payment`, `Appeal`.
- **`app/Console/Commands/PruneStaleData.php`** `tems:prune` (activity_log, sessions,
  failed_jobs, read notifications; `--days=180`) + optional `--compact-archives` to null
  legacy snapshots whose record still exists. Scheduled daily in `routes/console.php`.
- Verified: `php -l` all touched files, `view:cache` cycle, **17/17 tests pass**.

### Recommended one-time storage reclaim
Run once to drop the legacy JSON bloat already in the DB (safe — only compacts where the
source record still exists):
```
php artisan tems:prune --compact-archives
```

---

## 6. Migrations (remaining)

1. *(P2)* ✅ rename `clamping_requests`→`citizen_reports` + add `report_number`,
   `vehicle_make/model/color`, `citation_id`, `resolution_type`, `resolution_notes`,
   `resolved_at`; drop `vehicle_description` and `reference_number`.
2. *(P3)* ✅ add `users.is_available` (+index).
3. *(P5)* drop `appeals.guest_name`, `appeals.guest_contact`.
4. *(P6)* add `citations.custom_violation_name`.
5. *(P7)* drop `clamping_records.vehicle_plate`, `impounding_records.vehicle_plate`,
   `clamping_records.{clamping_fee,payment_method,reference_number,paid_at}`.

---

## 7. Build Order

`P0 ✅ → P1 ✅ → P2 ✅ → P3 ✅ → P4 ✅ → P5 ✅ → P6 ✅ → P8 ✅ → P9 ✅ → P10 ✅ → P11 ✅ → P7`

(P7 last, so deduplication only drops columns after every reader is fetch-based.)

---

## 8. P1 — Completed Work (detail)

- **`app/Enums/Role.php`**: added `isSuperAdmin()`, `isCashier()`, `isFrontDesk()`, `canDelete()`.
- **`app/Models/User.php`**: mirrored `isSuperAdmin()`, `isCashier()`, `isFrontDesk()`, `canDelete()`.
- **Policies**: added `delete()` (SuperAdmin-only) to `Citation`, `Clamping`, `ImpoundingRecord`,
  `Payment`, `Appeal`, `ClampingRequest` policies. New `app/Policies/UserPolicy.php`
  (admin manage, SuperAdmin delete, no self-delete).
- **`UserController::destroy`**: SuperAdmin only; blocks deletion when the user has issued
  citations or recorded payments (archives a snapshot, detaches teams, clears
  notifications/devices, then deletes). `users.destroy` route enabled.
- **`TeamController::destroy` / `ZoneController::destroy`**: gated to SuperAdmin and now
  write an archive snapshot before deleting.
- **UI**: delete controls hidden from non-SuperAdmins (`users` dropdown, `teams/index`,
  `teams/edit` danger zone + modal, `zones/index` card actions and map overlay).
- Verified: `php -l` all touched files, `view:cache` cycle, **17/17 tests pass**.

> Note: destructive deletes for financial/legal transactional records (citations, payments,
> clamping, impounding, appeals) have `delete()` abilities defined but **no routes/UI yet** —
> deferred pending confirmation (P7 territory). Admin-config deletes (users/teams/zones) are live.

---

## 9. P2 — Completed Work (detail)

- **Migration** `2026_10_22_000001_rename_clamping_requests_to_citizen_reports.php` (applied
  to shared Supabase DB): renames table, adds `report_number` (unique), `vehicle_make/model/color`,
  `citation_id` (FK nullOnDelete), `resolution_type`, `resolution_notes`, `resolved_at`;
  backfills `report_number` from `reference_number` (fallback `RPT-######`); drops
  `reference_number` + `vehicle_description` (drops the legacy unique index first for SQLite);
  widens `status` and migrates `approved`→`accepted`; repoints
  `archives.archivable_type` `ClampingRequest`→`CitizenReport`.
- **New classes**: `app/Enums/ReportStatus.php` (Pending/'Under Review', Accepted, Resolved,
  Rejected), `app/Models/CitizenReport.php` (with `vehicle_description` accessor assembling
  color/make/model), `app/Policies/CitizenReportPolicy.php`,
  `app/Http/Controllers/CitizenReportController.php` (index/show/accept/approve alias/reject/
  assign/resolve). Deleted `ClampingRequest` model, policy, controller.
- **Routes**: `citizen-reports.` group, param `{citizenReport}`, added `accept` route.
- **Views**: new `citizen-reports/index.blade.php` + `show.blade.php`; deleted
  `clamping-requests/`. `citizen/{clamping-request,clamping-success,clamping-track}.blade.php`
  + `citizen/partials/clamping-track-panel.blade.php` updated (Full Name label, make/model/color
  inputs, `RPT-` ticket, status labels). `archives/index.blade.php` + `print.blade.php` handle
  both `CitizenReport` and legacy `ClampingRequest`.
- **Ref updates**: `DashboardController`, `ArchivesExport`, `ClampingController`,
  `FrontDeskController`, `Archive`, `User` (`assignedCitizenReports`), `NavigationComposer`,
  `PageTitleComposer`, `layouts/app.blade.php`, `CitationNumberService::reportNumber()`,
  `dashboard/index.blade.php`.
- **No auto-citation**: reports never create citations; only enforcer action does (P4).
- Verified: `php -l` all touched files, `view:cache` cycle, **17/17 tests pass**. Migration ran
  cleanly (`[26] Ran`).

---

## 10. P3 — Completed Work (detail)

- **Migration** `2026_10_22_000002_add_is_available_to_users_table.php` (applied to shared DB):
  adds `users.is_available` (boolean, default `true`, indexed).
- **`app/Models/User.php`**: `is_available` fillable + cast; new `enforcerLocation()` HasOne,
  `isAssignable()` (available AND online), `scopeAssignable`.
- **`app/Services/AutoAssignService.php`**: `pickEnforcer()` — random among approved/active
  Enforcers where `is_available = true AND is_online = true`; falls back to the enforcer with
  the fewest open reports when none is available.
- **`app/Http/Controllers/TaskAssignmentController.php`** (Operations): `index` (availability
  roster + unassigned/active report panels), `assign`, `autoAssign` (single), `autoAssignAll`,
  `toggleAvailability`. Admin-only via `authorizeAdmin()`.
- **Routes** (`routes/web.php`): group `operations.task-assignment.` (index, auto-assign-all,
  `{citizenReport}/assign`, `{citizenReport}/auto-assign`, `enforcers/{user}/toggle-availability`);
  plus enforcer self-service `availability.toggle`.
- **`CitizenReportController::accept`** now auto-picks an eligible enforcer (via
  `AutoAssignService`) when no `assigned_to` is supplied.
- **UI**: new `resources/views/operations/task-assignment.blade.php`; nav item **Task
  Assignment** under operations (admins) in `NavigationComposer`; page title mapping in
  `PageTitleComposer`; enforcer "More" sheet gained a self availability toggle
  (`layouts/app.blade.php`); enforcer GPS `location` update marks them online.
- Verified: `php -l` all touched files, `view:cache` cycle, `route:list` shows all 5+1 routes,
  **17/17 tests pass**. Migration ran cleanly.

---

## 11. P4 — Completed Work (detail)

- **`app/Policies/CitizenReportPolicy.php`**: `view`/`update` now allow an enforcer when the
  report is assigned to them **or** still `pending` (shared pool); added `claim` ability.
- **`app/Http/Controllers/CitizenReportController.php`**:
  - `claim` — enforcer takes a pending report (assigns to self, status `accepted`).
  - `accept` — enforcer accepting assigns to self; admins pick an enforcer or fall back to
    `AutoAssignService`.
  - `issueCitation(StoreCitationRequest, …)` — creates a `Citation` pre-filled from the report
    (plate/make/model/color/location/coords), uploads evidence, auto-creates impounding when
    the violation is impoundable, links `citizen_reports.citation_id`, sets report
    `resolved` + `resolution_type='citation_issued'`, archives citation + report. Rejects
    duplicates when a citation already exists.
  - `resolve` — enforcer/admin closes with notes (`resolution_type='resolved_with_notes'`).
  - `approve` alias now forwards the injected `AutoAssignService`.
  - `show` passes active `violationTypes` for the citation modal.
- **Routes** (`routes/web.php`, `citizen-reports.` group): added `{citizenReport}/claim` and
  `{citizenReport}/issue-citation`.
- **Views** (`resources/views/citizen-reports/show.blade.php`): header actions gain **Claim
  Report** (pool), **Issue Citation** + **Resolve with Notes** (accepted, no citation),
  **View Citation** link; new `issueCitationModal` (pre-filled, evidence upload → posts to
  `issue-citation`) and `resolveModal`; sticky action bar updated for enforcer flow.
- **Tests**: new `tests/Feature/CitizenReportWorkflowTest.php` (claim, reject, issue-citation
  + link, duplicate-block, resolve-with-notes, cross-enforcer forbidden). Suite now
  **23/23 pass, 63 assertions**.
- Reports still never auto-create citations — only the explicit enforcer action does.

---

## 12. P5 — Completed Work (detail)

- **Migration** `2026_10_22_000003_drop_guest_fields_from_appeals_table.php` (applied):
  drops `appeals.guest_name` + `guest_contact` (audit R6); idempotent guards +
  reversible `down()`.
- **Citizen submission removed**: deleted `CitizenPortalController::submitAppeal` (+ unused
  `Appeal`/`AppealStatus` imports) and the `public.citation.appeal` route; the citizen
  citation-detail page now shows a "visit the Front Desk to file an appeal" notice instead
  of the guest form.
- **`app/Policies/AppealPolicy.php`**: `create()` now grants **FrontDesk** (and SuperAdmin/
  Administrator) — VehicleOwners can no longer file.
- **`app/Models/Appeal.php`**: dropped `guest_name`/`guest_contact` from `$fillable`,
  removed `isGuestSubmission()`, simplified `submitterDisplay()`, added `ticketNumber()`
  (`APL-######`).
- **Views**:
  - `appeals/index.blade.php` — "File Appeal" button gated by `@can('create')`; added
    **Ticket #** + **Filed By** columns.
  - `appeals/create.blade.php` — reframed as FrontDesk "File Appeal" with old-value
    repopulation + validation errors.
  - `appeals/show.blade.php` — **redesigned two-column layout**: left = Appeal Summary
    container (metadata + reviewer + decision notes + review actions/modals), right =
    **full Citation Ticket** (violation, penalty, plate, vehicle, driver, dates, enforcer,
    location, evidence thumbnails, payment). Reject modal now captures decision notes.
  - `frontdesk/index.blade.php` — citation lookup gains an **Appeal tile**: shows the appeal
    ticket # + status with a **View Appeal** reason modal, or a **File Appeal** modal for
    appealable citations; list tiles show an appeal badge.
  - `citizen/citation-detail.blade.php` — guest appeal form replaced with FrontDesk notice.
- **`app/Http/Controllers/FrontDeskController.php`**: eager-loads `appeal.submitter` /
  `appeal.reviewer` for the detail lookup and `appeal` for the list.
- **`app/View/Composers/NavigationComposer.php`**: **Appeals** nav item now shown to
  FrontDesk.
- **Tests** (`tests/Feature/AppealWorkflowTest.php`): replaced the vehicle-owner submission
  test with a FrontDesk-files test; added vehicle-owner-forbidden, ticket-number, and
  guest-columns-removed tests. Suite now **26/26 pass, 68 assertions**.

---

## 13. P6 — Completed Work (detail)

- **Migration** `2026_10_22_000004_add_custom_violation_name_to_citations_table.php`
  (applied): adds `citations.custom_violation_name` (nullable, indexed n/a).
- **`app/Models/Citation.php`**: `custom_violation_name` fillable + `violationLabel()`
  accessor (returns the free-text name when set, else the violation type name) +
  `isCustomViolation()`; added to activity log `logOnly`.
- **`app/Models/ViolationType.php`**: `isCustom()` (code/name = `others`).
- **Seeder**: `DatabaseSeeder` now seeds an **`others`** violation type (penalty 0); inserted
  into the shared DB via `updateOrCreate`.
- **`app/Http/Requests/StoreCitationRequest.php`**: when the selected type is `others`,
  `custom_violation_name` and `penalty_amount` become **required** (conditional rules); the
  free-text penalty overrides the type penalty.
- **`app/Http/Controllers/CitationController::store`** and
  **`CitizenReportController::issueCitation`**: persist `custom_violation_name` and use the
  entered penalty for custom violations.
- **Views**: `citations/create.blade.php` and `citizen-reports/show.blade.php` issue-citation
  modal gain a custom-violation panel (description + penalty) toggled by `data-is-custom`.
  All violation displays now use `$citation->violationLabel()` — `citations/{show,ticket,
  print,handoff}`, `owner/{citations,dashboard}`, `frontdesk/index`, `citizen/citation-detail`
  + `citation-lookup`, `payments/{show,print,create}`, `impounding/{show,print-release,create}`,
  `clamping/create`, `appeals/{create,show}`.
- **Tests** (`tests/Feature/EvidenceRequiredTest.php`): added "Others requires description +
  penalty" and "Others stores custom name + penalty" cases. Suite now
  **28/28 pass, 76 assertions**.

---

## 14. P8 — Completed Work (detail)

- **`app/Services/UserKpiService.php`** (new): `contribution(User)` returns
  `{scope, cards[]}` for the "My Contribution" tiles, role-scoped:
  - **Cashier** — payments recorded, amount collected, receipts issued (printable via
    Archives), collected today.
  - **Enforcer** — citations issued, citizen reports resolved (of assigned), vehicles
    clamped, reports assigned.
  - **Admin/SuperAdmin** — system-wide citations, reports resolved, payments, revenue.
- **`app/Http/Controllers/DashboardController.php`**: injects `UserKpiService`; `index()`
  branches **Cashier → `cashierDashboard()`** (previously Cashiers got the admin dashboard);
  passes `$contribution` to the main (Admin/Enforcer) dashboard. Also fixed the owner
  dashboard `my_vehicles` stat key (view referenced a key the controller never provided).
- **`resources/views/dashboard/cashier.blade.php`** (new): focused cashier workspace —
  Record Payment / Receipts & Archives actions, "Today at a glance" tiles (collected today,
  payments today, unpaid, overdue, active clamps), quick actions, and a "My Recent Payments"
  table with per-row **Print** links.
- **`resources/views/dashboard/partials/my-contribution.blade.php`** (new): reusable
  "My Contribution" tile block, included by the main dashboard (covers Enforcer/Admin) and
  the cashier dashboard.
- **`app/Http/Controllers/ReportController.php`**: constructor now restricts all methods to
  `super_admin,administrator` **except** `overdueCitations` (also `cashier`); added
  `overdueCitations()` — unpaid citations (`issued`/`overdue`) past `due_date`, filterable by
  due-date range and search, grouped into overdue-age buckets (1–7 / 8–30 / 31–60 / 60+ days)
  with counts and amounts.
- **Routes / views**: `reports.overdue-citations` route; `reports/overdue-citations.blade.php`
  (age buckets + detail table with days-overdue); card added to `reports/index.blade.php`.
- **`app/View/Composers/NavigationComposer.php`**: Cashier nav now shows **Overdue Citations**
  (Monitoring) and **My Archives** (Operations) for receipt printing.
- **Tests** (`tests/Feature/CashierKpiAndOverdueTest.php`, 7 cases): cashier dashboard,
  cashier/enforcer/admin contribution scoping, cashier can view overdue report, enforcer
  forbidden, and overdue report excludes paid/not-yet-due citations. Suite now
  **35/35 pass, 95 assertions**.

---

## 15. P9 — Completed Work (detail)

- **`app/Http/Controllers/CitizenPortalController::citationPay()`** (new): renders the
  citizen-facing payment form at `GET cite/{id}/{token}/pay` (`public.citation.pay`),
  guarded by the citation validation token. The view `citizen/citation-pay.blade.php` shows
  citation details (violation, plate, amount, due date) **autofilled from the citation** and
  a confirmation form where the payer name defaults to the citation's `driver_name` (and can
  be edited).
- **`app/Http/Controllers/PayMongoController::publicCheckout()`** updated: accepts optional
  `payer_name` / `payer_phone` POST fields and uses them for the PayMongo billing
  `billing_name` / `billing_phone` (falling back to `driver_name` / null).
- **`resources/views/citizen/citation-pay.blade.php`** (new): autofilled payment form with
  read-only citation summary and editable payer fields; POSTs to the existing token-validated
  checkout endpoint.
- **`resources/views/citizen/citation-detail.blade.php`**: "Pay Online" button changed from a
  blind `POST public.citation.checkout` to a link to the new autofill page.
- **`resources/views/citizen/citation-lookup.blade.php`**: result footer now shows a **"Pay
  Online"** button (only when payable) linking to the autofill page; "Pay Citation" quick
  action changed from a "coming soon" alert to a real scroll-to-search link.
- **Route**: `GET cite/{id}/{token}/pay` added to `public.citation.` group.
- **Tests** (`tests/Feature/CitizenPaymentAutofillTest.php`, 4 cases): pay page autofills
  from citation, rejects bad token, shows already-paid state, lookup result offers online
  payment. Suite now **39/39 pass, 111 assertions**.

---

## 16. P11 — Completed Work (detail)

- **Test suite**: **39 tests / 111 assertions**, all passing (PHPUnit). New test files:
  - `tests/Feature/CashierKpiAndOverdueTest.php` (7) — cashier dashboard, role-scoped KPI, overdue report access.
  - `tests/Feature/CitizenPaymentAutofillTest.php` (4) — autofill pay page, token validation, paid-state guard, lookup integration.
- **Documentation**: `docs/NEW-REQUIREMENTS-PLAN.md` fully updated with status, build order, and detailed work logs for every completed phase (P0–P6, P8–P11).
- **Lint + view cache + test ritual** verified at each phase (`php -l`, `view:clear/cache`, `php artisan test`).
- **P7 (Column Deduplication)** remains intentionally last — per the Fetch-Only doctrine, column drops only after all readers are fetch-based.
