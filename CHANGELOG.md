# Changelog

## v4.5.0 — Docker Containerization

### Added
- **Dockerfile**: Multi-stage production build (Node Vite build → Composer install → PHP 8.3-fpm-alpine runtime)
- **Nginx config**: Optimised for Laravel with static asset caching, security headers, hidden file blocking
- **Supervisor config**: Runs nginx + php-fpm under a single supervisor process
- **.dockerignore**: Excludes dev artifacts, node_modules, vendor, storage caches from Docker context
- **Startup script**: Generates .env from environment, runs key generation, storage:link, and migrations before launching supervisor
- **render.yaml**: Render blueprint with Docker web service + free PostgreSQL database, auto-deploy from GitHub
- **Health check**: `/health` endpoint returns 200 for Render's health check pings

## v4.0.0 — Dashboard Overhaul, Archives Rework, Zone Maps & Layout Stability

### Added
- **Settings page**: Dedicated view with session, mail, and app configuration display
- **Auto-archiving**: Resolved clamping requests, paid citations, and approved/rejected appeals are automatically archived with full snapshot
- **Interactive zone map on dashboard**: Zone coverage map with team-colored markers powered by Maplibre
- **Zone map viewer**: Marker hover circles, fly-to, popups on zones index page
- **Zone toggle active/inactive**: PATCH endpoint to toggle zone status with model refresh
- **Database migrations**: Constraints/indexes, user online status/preferences, zone address field
- **Print release view**: Dedicated printable release form for impounding
- **Account procedure watermark**: Semi-transparent logo centered behind login/register form panels

### Changed
- **Dashboard fully redesigned**: 5 KPI cards with week-over-week trends, Analytics 2×2 grid (citations trend, payment trend, top violations, zone coverage), Recent Activity feed, Pending Work Queue, Quick Actions
- **Archives reworked**: Interactive card-based UI with inline expandable snapshot details and type filter
- **Zones index rewritten**: Map-first layout with scrollable zone list, stats bar, search/filter, team-colored markers
- **Sidebar brand**: Stacked "Transportation Enforcement Management System" words with highlighted first letters
- **Sidebar layout**: Fixed positioning with stable zoom-resistant width; no horizontal scroll at any zoom level
- **Dashboard grid**: Replaced `col` auto-fit with `row-cols-2/3/5` for consistent layout across zoom levels
- **System rebrand**: All "ITEVCMS" references replaced with "TEMs"; "Land Transportation Enforcement" replaced with "Transportation Enforcement Management System"
- **Guest/auth views**: Updated brand text, alt attributes, and layout consistency across all auth pages

### Fixed
- **ToggleActive not refreshing model**: Replaced `update()` with `save()` so the zone object reflects new state immediately
- **PostgreSQL HAVING incompatibility**: Top violations filtered in PHP instead of using `havingRaw` (which PG does not support on computed aliases)
- **Route cache miss**: `zones.toggle-active` registered properly; route cache rebuilt
- **Dashboard map not rendering**: Added `@vite('resources/js/zone-picker.js')` entry point; removed CDN Maplibre imports
- **Sidebar cropping at high zoom**: Position fixed + min-width: 0 + overflow-x: hidden on outer wrapper
- **SettingsController crash with file session driver**: Guarded `DB::table('sessions')` with `Schema::hasTable('sessions')`
- **Supabase registration fallback**: Connection failures caught gracefully; local-only creation as fallback
- **Sidebar brand text overflow**: White-space nowrap and overflow hidden on brand lines

## v3.2.0 — Impounding Referral

### Added
- **Enforcer impounding referral**: On the citation show page, if the violation is `is_impoundable` and no clamp exists yet, Enforcers/Admins see a "Refer for Impounding" button that creates a `ClampingRecord` (status `AwaitingPayment`) and redirects to the impounding page.

### Fixed
- **500 error on registration/login**: Supabase HTTP calls now catch `ConnectionException` gracefully. `attempt()` throws `ValidationException` instead of returning `null` (which caused `Auth::login(null)` → TypeError 500). Registration falls back to local-only user creation if Supabase is unreachable.

## v3.1.0 — Impounding Management + Clamping Request Overhaul

### Added
- **Impounding module**: Full workflow (Awaiting Payment → Paid → Waiting Release → Released → Archived) with dedicated `ImpoundingController` and views (index with filters/inline modals, show with timeline)
- **VehicleRelease model**: Tracks vehicle releases with release number, notes, timestamps, linked to `ClampingRecord`
- **Comprehensive LTO violations**: 15 violation types seeded with `is_impoundable` flag and accurate penalty amounts
- **Clamping request management**: New `ClampingRequestController`, policy, views (index+show), approve/reject/assign/resolve actions
- **ClampingRequest `assigned_to` FK**: Migration adding the column for officer assignment
- **Map radius fix**: Zone tracking circles converted to GeoJSON polygon circles for proper zoom-responsive scaling

### Changed
- **ClampingStatus enum**: `Active` → `AwaitingPayment`; added `Paid`, `WaitingRelease`, `Released`
- **Clamping request form redesigned**: Option B layout — wider container (1100px), 2-column top row (Reporter Info + Location/Map), full-width Vehicle Info & Evidence cards, GPS pinning with map click fine-tune
- **Guest layout**: Added `@stack('styles')` (was missing, breaking child page CSS overrides)
- **Payment permissions**: `PaymentPolicy@create` restricted to Cashier; added `update` gate
- **Clamping show view**: Fixed broken link to vehicle releases (now points to impounding detail)
- **Navigation**: Added Impounding + Clamping Requests nav links

### Fixed
- **Map not rendering**: Added `@stack('scripts')` to `guest.blade.php` — Maplibre JS was never executed
- **Clamping form layout**: Container width constraint removed, replaced with responsive 1100px wrapper
- **Payment edit view**: Removed stale `citation.vehicle` references

## v3.0.0 — Initial Release

### Added
- Vehicle/Driver management with removal support
- UI redesign with responsive layouts
- Interactive zone maps with real-time tracking
- TomSelect-powered member selection
- TEM integration with VCMS
- Transportation enforcement management core
