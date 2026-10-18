# Violation Types Change Log / Gap Documentation

Gap documentation for the Violation Types feature. Follow the template in
`documents/documentation_Reference.md`. Append new entries at the end, newest last.

---

## Violation Type Deactivation — Missing Management UI (Gap)

### Date Edited / Applied `(required)`

- Documented: 2026-10-07

### Type of Change `(required)`

- Documentation of a missing / unintended / not-yet-planned feature (no code changed by this entry).

### Requested By / Source `(optional)`

- Surfaced during thesis-defense study review (Lesson 2, `documents/lesson.md`).
- Live area: `/citations/create` → "Select violation..." dropdown.

### Problem `(required)`

- The `violation_types` table has an `is_active` flag, and that flag is enforced in
  two places (the create-form query and the form-request validation), **but there is
  no UI, route, or controller anywhere in the system that can flip it.**
- The flag therefore has no way to be set to `false` through the application — it stays
  at its migration default (`true`) forever. An ordinance can be retired only by editing
  the database directly.
- Symptom: an administrator wanting to hide a repealed/retired violation from enforcers
  has no way to do it from the app. There is also no `Violation Types` item in the
  sidebar (`app/View/Composers/NavigationComposer.php` has no such entry).

### Root Cause `(required)`

- The migration and the defensive guards were built first; the management surface was
  never planned. The guard rails exist ahead of the feature ("safety first"), so the
  system is already safe the day the UI is added — but the feature itself is absent.
- Supporting evidence of absence:
  - `routes/web.php` — no `violation-types` route, no `ViolationTypeController` import.
  - `app/Http/Controllers/` — no `ViolationTypeController.php` (glob returned nothing).
  - Grep for `ViolationType` across `app/` returns only *read* usage:
    `CitationController` (read), `DashboardController` (read), `ReportController` (read),
    `StoreCitationRequest` (read/validate), `ViolationType.php` (model), `Citation.php`
    (relation). No write path.
  - The only writer to the table is `database/seeders/DatabaseSeeder.php:53`
    (`ViolationType::updateOrCreate`).

### Files Changed `(required)`

- `documents/ViolationTypesGap.md` (this log — documentation only)

### What Parts Changed `(required)`

#### `documents/ViolationTypesGap.md`

Created this log; no application code modified.

### The Guards That Already Exist (evidence)

The flag is checked correctly everywhere it is consumed today:

#### `database/migrations/2026_06_13_000004_create_violation_types_table.php`

```php
$table->boolean('is_active')->default(true);
```

#### `app/Http/Controllers/CitationController.php` (line 48 — `create()`)

Only active types are offered in the dropdown:

```php
'violationTypes' => ViolationType::where('is_active', true)->orderBy('name')->get(),
```

#### `app/Http/Requests/StoreCitationRequest.php` (lines 17–21)

Re-validated at submit time — a crafted request cannot bypass the dropdown:

```php
'violation_type_id' => ['required', 'exists:violation_types,id', function ($attribute, $value, $fail) {
    if (! \App\Models\ViolationType::where('id', $value)->where('is_active', true)->exists()) {
        $fail('The selected violation type is not available.');
    }
}],
```

### Behavior of the New Changes `(required)`

No behavior changed. Documenting current vs. intended state:

| Scenario | Before (current) | After (proposed fix, not yet implemented) |
|---|---|---|
| Retire a violation ordinance | Requires direct SQL on `violation_types.is_active` | Admin toggles it from the Violation Types page |
| Enforcer dropdown (`/citations/create`) | Already excludes inactive — unchanged | Unchanged |
| Submit request with inactive id | Already rejected with "not available" — unchanged | Unchanged |
| Visibility of the flag | Not visible anywhere in the app | Visible with status badge on the new page |

### Impact & Risk `(required)`

- **Current risk: LOW.** The flag is always `true`, so the two guards are effectively
  inert and cannot break anything.
- **Functional gap: MEDIUM.** The system cannot express "this violation is retired,"
  which the schema and both guards clearly anticipated.
- **Affects:** `/citations/create` (dropdown), `StoreCitationRequest`, reports that
  group by `violationType` (unchanged — history must remain reportable).
- **Defense framing:** present this as *"the guard rails were built first; the
  management surface is a planned follow-up"* rather than an oversight — the code
  already handles the failure state safely.

### Proposed Fix (not yet implemented) `(optional)`

Goal: give admins a way to view and toggle `is_active`, respecting the existing
`role` middleware pattern used by Reports and Audit Logs.

**New files:**

| Layer | File |
|---|---|
| Controller | `app/Http/Controllers/ViolationTypeController.php` |
| Route | in `routes/web.php`, inside the `['auth','active','approved']` group |
| View | `resources/views/violation-types/index.blade.php` |

**Routes:**

```php
Route::middleware('role:super_admin,administrator')->prefix('violation-types')->name('violation-types.')->group(function () {
    Route::get('/', [ViolationTypeController::class, 'index'])->name('index');
    Route::post('{violationType}/toggle-active', [ViolationTypeController::class, 'toggleActive'])->name('toggle-active');
});
```

**Controller sketch:**

```php
class ViolationTypeController extends Controller
{
    public function index(): View
    {
        return view('violation-types.index', [
            'violationTypes' => ViolationType::withCount('citations')->orderBy('name')->get(),
        ]);
    }

    public function toggleActive(ViolationType $violationType)
    {
        $violationType->update(['is_active' => ! $violationType->is_active]);

        return back()->with('success', $violationType->is_active
            ? $violationType->code.' reactivated.'
            : $violationType->code.' deactivated. It is hidden from new citations.');
    }
}
```

**Model note:** no change needed — `app/Models/ViolationType.php` already has
`is_active` in `$fillable`, already casts `'is_active' => 'boolean'`, and already
defines the `citations()` relation that `withCount('citations')` relies on.

**Navigation:** add a `Violation Types` item to the ADMINISTRATION group in
`app/View/Composers/NavigationComposer.php`, gated to admins.

**Why `withCount('citations')`:** before deactivating, an admin should see how many
citations already reference that type. Historical citations keep working either way —
`Citation::violationType()` is a plain `belongsTo`, so already-issued tickets still
render their violation name.

### Database / Migration Impact `(optional)`

- None. The `is_active` column already exists (`create_violation_types_table.php`).
  No schema change needed.

### Untouched `(optional)`

- `StoreCitationRequest` — both existing guards stay as-is (defense in depth).
- `CitationController::create()` dropdown query — unchanged.
- Seeders and violation data — unchanged.
- Historical citations and reports — unchanged.

### Known Issues / Follow-ups `(optional)`

- This entry documents the gap only; **the fix above is a proposal, not implemented.**
- Optional pre-defense task: implementing this is a ~1 session feature and is a good
  live demo because it shows validation guard + admin-only route + toggle pattern.
- Related safety note already present in code: `CitationController::referToImpounding`
  reads `$citation->violationType?->is_impoundable` — the `?->` null-safe
  operator means a missing/deleted violation type will not crash the page.

### Testing / Verification `(required)`

Absence of the feature verified by:

- Grep `violation_types|ViolationType|is_active` in `routes/` → no matches.
- Grep `ViolationType` across `app/` → only read/validate usages listed in Root Cause.
- Glob `app/Http/Controllers/*Violation*` → no files found.
- Grep `violation` across `resources/views/` → no management view exists.
- Current guards verified by reading the two files cited in "The Guards That Already Exist".
