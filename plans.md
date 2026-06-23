# Porting TEMs v1.5.3 Features → transenfo (ITEVCMS)

## Overview

This plan identifies features present in the original **TEMs** (plain PHP/MySQL) that are missing from **transenfo/ITEVCMS** (Laravel 13 + PostgreSQL + Vite), with implementation steps for each.

---

## Phase 1 — Core Enforcement Workflow

### 1.1 Duty Status for Enforcers

TEMs has an `officers` table with `duty_status` (online/offline/break). Transenfo tracks enforcer GPS via `EnforcerLocation` but has no duty status on the user.

Instead of a full `Officer` model, add `duty_status` directly to `users`.

**Migration:** `2026_06_15_000001_add_duty_status_to_users.php`
- `duty_status` VARCHAR(20) default `offline`

**Enum:** `app/Enums/DutyStatus.php`
- Cases: `OnDuty`, `OnBreak`, `Offline`
- Methods: `label()`, `badgeClass()`

**User model update:**
- Add `duty_status` to `$fillable`
- Cast: `duty_status` => `DutyStatus::class`
- Helpers: `isOnDuty()`, `isOnBreak()`, `isOffline()`

**Controller method** (add to `ProfileController` or a new lightweight endpoint):
- `POST profile/duty-status` — toggles/updates duty_status
- Accessible by enforcer/clamping_officer roles

**Routes:**
```php
Route::post('profile/duty-status', [ProfileController::class, 'updateDutyStatus'])->name('profile.duty-status');
```

**Views:** Update `users/index.blade.php` and `profile/edit.blade.php` to show/change duty_status badge.

---

## Phase 2 — User-Facing & Operations

### 2.1 Password Reset

Laravel's boilerplate `password_reset_tokens` table exists, but no routes/controllers/views.

**Controller:** `app/Http/Controllers/Auth/ForgotPasswordController.php`
- `showLinkRequestForm()` → returns view
- `sendResetLinkEmail(Request)` → validates email, uses `Password::sendResetLink()`, redirects with status

**Controller:** `app/Http/Controllers/Auth/ResetPasswordController.php`
- `showResetForm($token)` → returns view with token
- `reset(Request)` → validates, uses `Password::reset()`, flashes success, redirects to login

**Views:**
- `resources/views/auth/forgot-password.blade.php` — email input, submits via POST
- `resources/views/auth/reset-password.blade.php` — token hidden, password + confirm
- Both use `layouts.guest`

**Routes** (in guest group):
```php
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
```

**Note:** Uses Laravel's built-in `Illuminate\Auth\Notifications\ResetPassword`. Mail config must be set up in `.env`.

**Add "Forgot password?" link on login view.**

---

### 2.2 Database Backup

TEMs has a backup module using `mysqldump`. Transenfo has none.

**Service:** `app/Services/BackupService.php`
- `createBackup()`: Uses `Illuminate\Support\Facades\Process` to run `pg_dump` (since transenfo uses PostgreSQL), saves to `storage/app/backups/`
- `listBackups()`: Returns sorted array of backup files with size/date
- `getBackupPath($filename)`: Returns full path

**Controller:** `app/Http/Controllers/BackupController.php`
- `index()` — lists backups
- `store()` — creates new backup, flashes success/error
- `download($filename)` — returns `Storage::download()`
- Middleware: `role:super_admin,administrator`

**Views:** `resources/views/backups/index.blade.php`
- Top: "Backup Now" button with last backup info
- Table: filename, size, created_at, download button per row

**Routes:**
```php
Route::middleware('role:super_admin,administrator')->prefix('backups')->name('backups.')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::post('/', [BackupController::class, 'store'])->name('store');
    Route::get('{filename}/download', [BackupController::class, 'download'])->name('download');
});
```

**Navigation:** Add `Backups` item under admin section in `NavigationComposer`.

---

### 2.3 Print Tracking

TEMs tracks when a citation is printed (`printed_at`). Transenfo has no print tracking.

**Migration:** `2026_06_15_000004_add_print_fields_to_citations.php`
- `printed_at` timestamp nullable
- `printed_by` FK → `users` nullable

**Citation model update:**
- Add `printed_at`, `printed_by` to `$fillable`
- Casts: `printed_at` => `datetime`
- Relationship: `printedBy()` belongsTo(User)
- Helper: `isPrinted(): bool`

**Controller method** (add to `CitationController`):
- `print(Citation $citation)`: Sets `printed_at`, `printed_by` on first print, returns print-friendly view

**View:** `resources/views/citations/print.blade.php`
- Minimal layout (no sidebar, no nav, no Bootstrap — just clean printable HTML)
- Citation number, vehicle, driver, violation type, amount, issued date, QR code

**Routes:**
```php
Route::get('citations/{citation}/print', [CitationController::class, 'print'])->name('citations.print');
```

**Update `citations/show.blade.php`:** Add "Print" button linking to `route('citations.print', $citation)`, gated by `@can('update', $citation)`.

---

## Phase 3 — Quality of Life

### 3.1 Fix Passwords Command

TEMs has `fix-passwords.php`. Transenfo needs an Artisan equivalent.

**Command:** `app/Console/Commands/ResetDemoPasswords.php`
- Signature: `citations:reset-demo-passwords`
- Reads demo accounts from config or hardcoded array
- Updates each user's `password` to known value
- Outputs table of account name, email, new password

**Sample passwords for development:**
| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@itevcms.local | admin123 |
| Enforcer | enforcer@itevcms.local | enforcer123 |
| Cashier | cashier@itevcms.local | cashier123 |
| Clamping Officer | clamping@itevcms.local | clamp123 |
| Vehicle Owner | owner@itevcms.local | owner123 |

---

## Phase 4 — Public-Facing Features

### 4.1 Landing Page (Route Root `/` to Welcome Page)

Transenfo has landing page files (`welcome.blade.php`, `welcome-new.blade.php`) but `/` redirects to login.

**Current route** (`routes/web.php:28`):
```php
Route::get('/', fn () => redirect()->route('account.procedure'));
```

**Change to:**
```php
Route::get('/', function () {
    return view('welcome'); // or welcome-new
})->name('home');
```

**View cleanup:**
- Pick `welcome.blade.php` or `welcome-new.blade.php` as the main landing page
- Ensure navbar links (`Sign In`, `Sign Up`, `Citation Lookup`, `Report Parking`) point to correct named routes

---

### 4.2 Citizen Portal Appeal Filing

TEMs has a public appeal page. Transenfo only has staff-side appeal management.

**Controller method** (add to `CitizenPortalController`):
- `showAppealForm()` — renders appeal form
- `submitAppeal(Request)` — validates, finds citation by number, creates Appeal record with status `submitted`, redirects with success

**Request:** `app/Http/Requests/StoreCitizenAppealRequest.php`
- citation_number: exists in citations table
- appellant_name: required
- email: required, email
- phone: optional
- reason: required, min 10 chars

**View:** `resources/views/citizen/appeal.blade.php`
- Form with citation_number, appellant_name, email, phone, reason textarea
- Uses `layouts.guest`

**Routes** (in guest group):
```php
Route::prefix('citizen')->name('citizen.')->group(function () {
    // ... existing routes ...
    Route::get('appeal', [CitizenPortalController::class, 'showAppealForm'])->name('appeal');
    Route::post('appeal', [CitizenPortalController::class, 'submitAppeal'])->name('appeal.submit');
});
```

**Add "File an Appeal" link on landing page navbar and citation detail page.**

---

## File Summary

### New Files (Create)

| Layer | Count | Files |
|-------|-------|-------|
| Migrations | 2 | `add_duty_status_to_users`, `add_print_fields_to_citations` |
| Enums | 1 | `DutyStatus` |
| Controllers | 3 | `ForgotPasswordController`, `ResetPasswordController`, `BackupController` |
| Services | 1 | `BackupService` |
| Commands | 1 | `ResetDemoPasswords` |
| Requests | 1 | `StoreCitizenAppealRequest` |
| Views | 5 | `auth/forgot-password`, `auth/reset-password`, `backups/index`, `citations/print`, `citizen/appeal` |

### Existing Files (Modify)

| File | Changes |
|------|---------|
| `app/Models/User.php` | Add `duty_status` to fillable/casts, `isOnDuty()`, `isOnBreak()`, `isOffline()` |
| `app/Models/Citation.php` | Add `printed_at`, `printed_by` to fillable/casts, `printedBy()` relation, `isPrinted()` |
| `app/Http/Controllers/CitationController.php` | Add `print()` method |
| `app/Http/Controllers/CitizenPortalController.php` | Add `showAppealForm()`, `submitAppeal()` methods |
| `app/Http/Controllers/ProfileController.php` | Add `updateDutyStatus()` method |
| `routes/web.php` | Add duty status, password reset, backup, print, citizen appeal, landing page routes |
| `app/View/Composers/NavigationComposer.php` | Add Backups nav item |
| `resources/views/citations/show.blade.php` | Add Print button |
| `resources/views/users/index.blade.php` | Add duty_status badge column |
| `resources/views/profile/edit.blade.php` | Add duty status selector |
| `resources/views/auth/login.blade.php` | Add "Forgot password?" link |

---

## Implementation Order (Recommended)

1. **Duty Status** — small migration + model change + profile toggle
2. **Password Reset** — user-facing requirement
3. **Print Tracking** — small addition to citations
4. **Database Backup** — operational tool
5. **Fix Passwords Command** — developer QoL
6. **Landing Page Routing** — point `/` to welcome page
7. **Citizen Portal Appeal Filing** — public appeal submission
