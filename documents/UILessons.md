# TEMS UI/UX Lessons

Living log of pair-coding lessons on the TEMS UI/UX layer. Each session follows the same
loop: **open a real file → explain what it does and why → make one small change together.**

## Learner profile

- Comfortable with **HTML/CSS**.
- Still building confidence in **JavaScript** and **Laravel/Blade**.
- Goal: **maintain and extend existing pages** (modify views, add fields/columns, adjust styling).

## How each session runs

1. Read-only walkthrough of a real file, top to bottom.
2. Ask "what/why" freely.
3. One small hands-on task, then verify the result.

## Progress tracker

| # | Session | Main file(s) | Exercise | Status |
|---|---------|--------------|----------|--------|
| 1 | HTML → Blade | `resources/views/layouts/app.blade.php` | Trace a slot from fill to render | Not started |
| 2 | Anatomy of a list page | `resources/views/citations/index.blade.php` | Add a new table column | Not started |
| 3 | Forms end-to-end | `resources/views/citations/create.blade.php`, `app/Http/Requests/StoreCitationRequest.php` | Add one field through the full chain | Not started |
| 4 | The design system | `resources/css/app.css` | Restyle a card / add a badge variant | Not started |
| 5 | JS interaction patterns | `resources/views/dashboard/index.blade.php`, `resources/js/zone-picker.js` | Add confirm-before-submit or a toggle | Not started |
| 6 | Layout, nav & roles | `app/View/Composers/NavigationComposer.php`, `PageTitleComposer.php` | Add nav item / role-gate a button | Not started |

Status values: `Not started` · `In progress` · `Done` · `Needs review`.

---

## Architecture mental model

```
Route → Controller → returns view('module.page')
                          │
                          ▼
        Blade view @extends('layouts.app' or 'layouts.guest')
                          │
        @section('content') + @push('styles') + @push('scripts')
                          │
        Vite bundles resources/css/app.css + resources/js/app.js
```

### The five UI layers

| Layer | Files | What it does |
|-------|-------|--------------|
| 1. Layouts | `layouts/app.blade.php`, `layouts/guest.blade.php` | Page shell, sidebar, topbar, `@yield`/`@stack` hooks |
| 2. View composers | `app/View/Composers/*.php` | Inject `$navGroups` (role-based nav) and `$pageTitle` into layouts |
| 3. Components / partials | `views/components/alerts.blade.php`, `views/partials/*` | Reusable snippets |
| 4. Styling | `resources/css/app.css` | Bootstrap 5 import + design tokens (`:root`) + custom classes |
| 5. Interactivity | `views/**` `@push('scripts')`, `resources/js/*.js` | Vanilla JS, Chart.js, MapLibre, TomSelect, fetch + CSRF |

### Core conventions

- **Blade is HTML + PHP directives.** The five that matter most: `@extends`, `@section`,
  `@yield`, `@push`/`@stack`, `{{ }}`.
- **Styling:** Bootstrap 5 utilities plus semantic classes (`stat-card`, `panel`,
  `status-badge`, `tag-green`). Colors are CSS variables (`--itevcms-*`).
- **Status colors:** rule is `green/blue/red/amber/gray` (`app.css` ~line 1112), exposed via
  enum `label()` + `badgeClass()`.
- **JS:** no framework. `DOMContentLoaded`, `document.getElementById`, `fetch` with
  `X-CSRF-TOKEN` read from the `<meta name="csrf-token">` tag in the layout.
- **Mobile:** enforcers get a bottom tab-bar shell (`layouts/app.blade.php`), driven by
  `$isEnforcerMobile` from `NavigationComposer`.

### Handy utilities / commands

- Dev: `npm run dev` (Vite) and `composer dev` (serve + queue + logs + vite).
- Build assets: `npm run build`.
- Tests: `php artisan test`.

---

## Session 1 — HTML → Blade

**File:** `resources/views/layouts/app.blade.php`

**Concepts**
- `@extends('layouts.app')` — this page uses the layout as its template.
- `@section('content')` / `@yield('content')` — fill a named slot.
- `@push('styles')` / `@stack('styles')` — append assets into the layout's slots.
- `{{ $value }}` — echo (HTML-escaped); `{!! $html !!}` — raw echo (rare, careful).

**Why it matters:** every staff page extends this one file, so the sidebar/topbar live in a
single place.

**Exercise:** follow one variable (e.g. `$pageTitle`) from the composer that fills it to the
point it renders in the layout.

**Notes / log:**
- _(to be filled during the session)_

---

## Session 2 — Anatomy of a list page

**File:** `resources/views/citations/index.blade.php`

**Concepts**
- Controller → view data flow; reading query params with `request()`.
- `@foreach` vs `@forelse`; `@empty` fallback.
- `@can('create', Model::class)` — permission-gated buttons.
- The filter dropdown + debounced search JS (`index.blade.php` ~line 200): DOM events,
  `setTimeout` debounce, `form.submit()`.

**Exercise:** add a new column to the table.

**Notes / log:**
- _(to be filled during the session)_

---

## Session 3 — Forms end-to-end

**Files:** `resources/views/citations/create.blade.php`,
`app/Http/Requests/StoreCitationRequest.php`

**Concepts**
- `@csrf` — required hidden token on every POST form.
- `old('field')` — repopulates input after a failed validation.
- `@selected` / `@error` / `$errors` — select state and inline validation.
- Submit-spinner pattern (`create.blade.php` ~line 143).
- The chain: form field → request validation → model `$fillable` → database column.

**Exercise:** add one field and wire it through the full chain.

**Notes / log:**
- _(to be filled during the session)_

---

## Session 4 — The design system

**Files:** `resources/css/app.css`, plus a view's `@push('styles')`

**Concepts**
- CSS variables in `:root` (`--itevcms-primary`, `--itevcms-surface`, ...).
- Component classes: `.stat-card`, `.panel`, `.status-badge`, `.tag-*`, `.queue-*`, `.qa-btn`.
- The status color rule (`green/blue/red/amber/gray`) and `.status-badge-*` variants.
- Bootstrap utility vs custom class: when to use which.

**Exercise:** restyle a card or add a new badge variant.

**Notes / log:**
- _(to be filled during the session)_

---

## Session 5 — JS interaction patterns

**Files:** `resources/views/dashboard/index.blade.php`, `resources/js/zone-picker.js`

**Concepts**
- `DOMContentLoaded` + element lookup + event listeners.
- `fetch` POST with `X-CSRF-TOKEN` and `Content-Type` headers (GPS toggle).
- Chart.js init with empty-state handling and CDN fallback.
- MapLibre via `window.__zonePicker.initZoneViewer(...)`.

**Exercise:** add a confirm-before-submit or a show/hide toggle.

**Notes / log:**
- _(to be filled during the session)_

---

## Session 6 — Layout, nav & roles

**Files:** `app/View/Composers/NavigationComposer.php`,
`app/View/Composers/PageTitleComposer.php`

**Concepts**
- `View::composer(...)` registration in `AppServiceProvider`.
- Role-based menu construction with the `Role` enum.
- `@can` (policy) vs `isRole()` (role check) — which to use when.
- `$isEnforcerMobile` and the mobile tab-bar.

**Exercise:** add a nav item or role-gate a button.

**Notes / log:**
- _(to be filled during the session)_

---

## Open questions / follow-ups

- _(add items as we discover them during sessions)_
