# Require Photo Evidence when Issuing Citations, Clamps, and Impounds

### Date Edited / Applied `(required)`

```
- Edited and applied: 2026-10-12
```

### Type of Change `(required)`

```
- Feature (validation + UI)
```

### Requested By / Source `(optional)`

```
- Requested by project owner. Observed on the Issue Citation form
  (/citations/create) and Record Clamp form (/clamping/create): evidence was
  optional, so a citation or clamp could be saved with no photo.
```

### Problem `(required)`

- The system allowed citations, clamp records, and impounding records to be
  saved **without any photo evidence**. The photo is needed to prove the
  violation/clamp/impound, so it should be mandatory.
- Location: `/citations/create` (Evidence Photos), `/clamping/create`
  (Evidence Photo), `/impounding/create` (Photo of Vehicle).
- User-visible symptom: submitting with no photo still created the record.

### Root Cause `(required)`

- Server-side validation treated evidence as optional:
  - `StoreCitationRequest` had `'evidence' => ['nullable', ...]`.
  - `StoreClampingRequest` had `'evidence' => ['nullable', ...]`.
  - `ImpoundingController::store()` used `'evidence' => 'nullable|image|...'`.
- The Issue Citation label had no required marker and no inline error message.
  (Impounding already showed a `*` and inline error, but the server still
  allowed a null value; the Clamp form had neither.)
- Note: the browser `required` attribute cannot be used on the Issue Citation
  file inputs because they are hidden (`class="d-none"`); browsers cannot focus
  hidden fields. Enforcement therefore lives on the server (and shows inline).

### Files Changed `(required)`

- `app/Http/Requests/StoreCitationRequest.php`
- `resources/views/citations/create.blade.php`
- `app/Http/Requests/StoreClampingRequest.php`
- `resources/views/clamping/create.blade.php`
- `app/Http/Controllers/ImpoundingController.php`
- `tests/Feature/EvidenceRequiredTest.php`
- `documents/EvidenceRequired.md`

### What Parts Changed `(required)`

#### `app/Http/Requests/StoreCitationRequest.php`

**Before:**

```php
'evidence' => ['nullable', 'array', 'max:10'],
'evidence.*' => ['file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
```

**After:**

```php
'evidence' => ['required', 'array', 'min:1', 'max:10'],
'evidence.*' => ['file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
```

Plus a `messages()` method with friendly text (e.g. "At least one evidence photo
is required.").

#### `resources/views/citations/create.blade.php`

**Before:**

```blade
<label class="form-label">Evidence Photos</label>
...
<small class="form-text text-muted d-block mt-2">You may attach multiple photos.</small>
<div class="evidence-preview row g-2 mt-2" id="evidencePreview"></div>
</div>
```

**After:**

```blade
<label class="form-label">Evidence Photos <span class="text-danger">*</span></label>
...
<small class="form-text text-muted d-block mt-2">At least one photo is required. You may attach multiple photos.</small>
<div class="evidence-preview row g-2 mt-2" id="evidencePreview"></div>
@error('evidence')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
</div>
```

#### `app/Http/Requests/StoreClampingRequest.php`

**Before:**

```php
'evidence' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
```

**After:**

```php
'evidence' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
```

Plus a `messages()` method.

#### `resources/views/clamping/create.blade.php`

**Before:**

```blade
<label class="form-label">Evidence Photo</label>
<input type="file" name="evidence" class="form-control" accept="image/*">
```

**After:**

```blade
<label class="form-label">Evidence Photo <span class="text-danger">*</span></label>
<input type="file" name="evidence" class="form-control @error('evidence') is-invalid @enderror" accept="image/*" required>
@error('evidence')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
```

#### `app/Http/Controllers/ImpoundingController.php`

**Before:**

```php
'evidence' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
```

**After:**

```php
'evidence' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
```

Plus an inline messages array as the 2nd argument to `$request->validate(...)`.
(The impounding view already had the `*`, `required`, and inline `@error`.)

### Behavior of the New Changes `(required)`

| Scenario | Before | After |
|---|---|---|
| Submit citation with no photo | Citation saved | Blocked; redirected back with red inline "At least one evidence photo is required." |
| Submit clamp with no photo | Clamp saved | Blocked; redirected back with red inline "An evidence photo is required." |
| Submit impound with no photo | Impound saved | Blocked; redirected back with red inline "An evidence photo is required." |
| Submit any of the three with a valid photo | Saved | Saved (unchanged) |
| Field label | No marker (citation/clamp) | Red `*` + helper text (citation/clamp) |

Validation failures redirect (HTTP 302) back to the form with the errors
flashed and old input preserved; the shared top alert
(`resources/views/components/alerts.blade.php`, rendered by the app layout) also
lists the message. No exception/500 occurs for a validation failure.

### Impact & Risk `(required)`

```
- Affects: /citations/create (store), /clamping/create (store),
  /impounding/create (store).
- Roles: SuperAdmin, Administrator, Enforcer (the roles allowed to create).
- Risk: existing flows/tests that create these records without a photo will now
  be rejected. The mobile/handoff flow still requires a photo, so no change
  there. No client-side (browser) enforcement was added for citations, so a
  user with JS disabled still gets the same server-side message.
```

### Database / Migration Impact `(optional)`

```
- None. No schema change, no seeder change, no backfill. Existing records are
  untouched. Enforcement is at validation only:
  - citations: "at least one evidence row" (child table) cannot be expressed as
    a DB constraint; enforced in the request.
  - clamping_records.evidence_path stays nullable so historical rows remain
    valid.
```

### Untouched `(optional)`

- Models, controllers (aside from the one-line impounding rule), routes, and
  policies were not changed.
- `ClampingController` / `CitationController` upload logic is unchanged; their
  `hasFile()` guards now always run because evidence is guaranteed.
- Impounding create view was already correct (red `*`, `required`, `@error`).
- Citizen-facing clamp request form (`citizen/clamping-request.blade.php`) is a
  different flow and was not changed.

### Known Issues / Follow-ups `(optional)`

- `ImpoundingController::store()` reads `$validated['notes']` and
  `$validated['location']` directly. If a client POSTs without those keys the
  route returns a 500 (pre-existing behavior; the real form always submits both).
  Recommended follow-up: use `$validated['notes'] ?? null`. Out of scope for
  this change.
- Blade `@error` cannot wildcard, so per-file errors (e.g. `evidence.0`) appear
  only in the shared top alert; the field-level inline message covers the
  required/`min` case.
- Browsers may cache CSS/JS; a hard reload (Ctrl+F5) may be needed after deploy.

### Testing / Verification `(required)`

- Added `tests/Feature/EvidenceRequiredTest.php` (6 tests):
  - citation/clamp/impound are rejected without a photo and no record is created;
  - citation/clamp/impound are created when a valid photo is supplied
    (Supabase upload faked with `Http::fake()`).
- Ran the full suite: `vendor/phpunit/phpunit/phpunit` → 14 passed, 34 assertions
  (8 pre-existing + 6 new).

  Local note: the standalone PHP at `C:\php-8.3` has `mbstring`, `pdo_sqlite`,
  and `sqlite3` commented out in `php.ini`, so tests were run with:

  ```
  php -d extension_dir="C:\php-8.3\ext" -d extension=mbstring \
      -d extension=pdo_sqlite -d extension=sqlite3 \
      vendor/phpunit/phpunit/phpunit
  ```

  (`php artisan test` spawns a child process that drops `-d` flags, so phpunit
  was invoked directly.)

- Blade syntax check: `php artisan view:cache` then `php artisan view:clear`.
- Manual: open each create form, submit with no photo → red inline message + top
  alert, no record; submit with a photo → record created.
