# TEMS Study Log — IT Defense Prep

Format: story-first teaching, every lesson ends with a "teach it back" check.

## Current Status
- [x] Lesson 1 — Enforcer's First Day (register → approve → login housekeeping)
- [x] Lesson 2 — The Life of a Citation (validation gate, transaction, enum)
- [~] Lesson 3 — The Money Line (validation thread ✅ done; 2 open: UNIQUE constraint + PaymentRecorder service)
- [ ] Next: finish Lesson 3 open answers OR Lesson 4 — Clamping & Impounding

## The Defense Mindset (3 layers)
WHAT does it do? → HOW is it built? → WHY this way?

## Lessons Log
| # | Lesson | Status | Key files | Teach-it-back |
|---|--------|--------|-----------|---------------|
| 1 | Enforcer's First Day | Done | LoginController.php, routes/web.php:97 | "Why do new enforcers register as 'pending'?" ✅ passed |
| 2 | Life of a Citation | Done | CitationController.php:53,59,72, StoreCitationRequest.php:14, CitationStatus.php:7-11 | transaction + enum ✅ passed |
| 3 | The Money Line | In progress | PaymentController.php:340,376, PaymentRecorder.php, StorePaymentRequest.php, Payment.php | validation thread ✅ (required_if/nullable/exists); open: UNIQUE constraint + service pattern |
| 4 | Clamping & Impounding | Pending | ClampingController, ImpoundingController | TBD |
| 5 | Database Under the Hood | Pending | migrations/ | TBD |
| 6 | Security & RBAC | Pending | Policies/, middleware, HMAC tokens | TBD |
| 7 | GPS, Zones & Tracking | Pending | TrackingController, zones migration | TBD |
| 8 | APIs & Integrations | Pending | routes/api.php, PayMongoService, SupabaseAuthService | TBD |
| 9 | Code-Level Deep Dive | Pending | CitationController::store, services/ | TBD |
| 10 | Why Laravel / Why Postgres | Pending | composer.json, database.php | TBD |

## Gap / Problem Docs (found while studying)
- `documents/ViolationTypesGap.md` — `violation_types.is_active` is guarded in 2 places but no UI/route/controller can flip it. Proposed fix included.
- `documents/CitationProccessProblem.md` — `vehicle_plate` has no format validation or case normalization; case mismatches bypass duplicate-clamp guards and case-sensitive LIKE search. Proposed fix included.

## Demo Script (practice flows)
- (to be written in Phase C, Days 9–14)

## Question Bank
- (to be built in Phase B, Days 5–8 — 60+ Q&A from real code)

## Cheat-sheet entries
- Accountability = registration's reason
- Supabase = identity; users table = role/status
- pending = safety gate before admin approval
- EnforcerLocation placeholder row = so tracking map always knows the enforcer
- DeviceManager = knowing which phone logged in, enables force-logout
- `route('account.pending')` = friendly nickname for URL `/account/pending`; dot = naming convention, unrelated to the `account_status` value
- Registration risk = anyone self-registering as an Enforcer; the pending gate fixes it
- 3-layer answer (what/how/why) = full-credit panel answer
- `DB::transaction` (CitationController.php:59) = citation + evidence + archive all-or-none; rollback on failure
- `rules()` (StoreCitationRequest.php:14) = auto-runs BEFORE `store()` via type-hint at CitationController.php:53; fail = store() never runs
- `CitationStatus` enum (CitationStatus.php:7-11) = 5 cases only; typo = loud dev-time error not silent bug; label() = display, badgeClass() = CSS; protects PHP layer (DB column is string)
- Validation = input INTEGRITY (safe to store), not CORRECTNESS (is it a real plate — human + evidence photo's job)
- CSS `text-uppercase` = display only, never changes submitted data
- `category` (payments/create.blade.php:21) = which thing is paid for: citation | clamping | impounding; carried as hidden input (create.blade.php:89) into the POST
- `required_if:category,impounding` (StorePaymentRequest.php:22) = field becomes required ONLY when category == impounding; otherwise optional
- `nullable` = empty allowed + SHORT-CIRCUITS (remaining rules skipped). Only "rescues" a field when required_if left it optional
- `exists:table,column` = audits the given id against a real row (SQL count query); never fetches. Optional ≠ unchecked: empty passes, junk still fails
- 3 payment id rules = only the id matching the chosen category is required; the other two are optional (nullable)
- Version drift note: codebase grew — `App\Models\ImpoundingRecord`, `App\Enums\ImpoundingStatus`, `App\Services\PaymentRecorder`, `Payment::payable()` morph, `SupabaseStorage`, `Exports\ArchivesExport` now exist (not in the first scan)
- Payment safety = defense in depth: (1) `StorePaymentRequest` validation, (2) live `paid_at` check `PaymentController.php:376` + `PaymentRecorder.php:67`, (3) DB UNIQUE constraint `payments_citation_id_unique`
- `Rule::enum(PaymentMethod::class)` (StorePaymentRequest.php:24) = value must be one of the enum cases (fixed menu, like CitationStatus)
- `PaymentRecorder` service (PaymentRecorder.php) = single source of truth for recording citation/clamping/impounding payments; each wraps `DB::transaction` and notifies the enforcer
- `Payment::payable()` morph (Payment.php:88) = payment points to Citation OR ClampingRecord OR ImpoundingRecord via payable_type/payable_id
