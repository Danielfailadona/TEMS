# SYSTEM ADMINISTRATION AND MAINTENANCE WORKSHEET — TEMS

## PART A — SYSTEM PROFILE

**Group Name** : Team TEMS  
**Capstone Project Title** : Traffic Enforcement Management System (TEMS)  
**Project Manager** : [Your Name]  
**System Owner / Client** : Local Government Unit / Traffic Management Office  
**Date** : September 2026  
**Members** :
- [Member 1] — Backend Lead (Laravel, Payments, Webhooks)
- [Member 2] — Frontend/Map Lead (MapLibre, Tracking, Dashboard)
- [Member 3] — Database/API Lead (Migrations, Citations, Reports)
- [Member 4] — QA/DevOps (Testing, Render Deployment, CI/CD)
- [Member 5] — Documentation/PM

---

### 1. System Description
TEMS is a web-based Traffic Enforcement Management System that digitizes the entire citation lifecycle: from roadside violation issuance by enforcers (with GPS location capture), through citation review and payment (GCash/Maya via PayMongo), to admin tracking, reporting, and impounding workflows. It replaces paper tickets with QR-coded digital citations, provides real-time enforcer GPS tracking for dispatch, and gives citizens a self-service portal to view and pay violations. The system solves lost tickets, delayed payments, opaque enforcement, and lack of real-time field visibility.

---

### 2. System Purpose
Primary purpose: **Automate and modernize traffic citation management** — enabling enforcers to issue digital citations with GPS evidence, citizens to pay instantly via QR Ph/GCash/Maya, and administrators to monitor enforcement in real time, generate revenue reports, and manage impounding — all from a single auditable platform.

---

### 3. Target Users

| User Type | Who Are They? | What Can They Do? |
|-----------|---------------|-------------------|
| **Super Admin** | LGU IT / System Owner | Full system config: users, roles, violation types, zones, teams, system settings, audit logs |
| **Administrator** | Traffic Office Manager | Manage enforcers, review citations, approve impounding referrals, generate revenue/performance reports, oversee payments |
| **Enforcer / Clamping Officer** | Field personnel (mobile) | Issue citations (scan plate, pick violation, capture GPS + photos), view assigned zone, toggle GPS tracking for dispatch, handoff QR to violator |
| **Cashier** | Payment window staff | Record walk-in cash payments, print official receipts, view citation status |
| **Vehicle Owner / Citizen** | Public (no login required for lookup) | Scan QR or enter citation # to view ticket, pay online via PayMongo (GCash/Maya/Card), download receipt |
| **Team Leader** | Enforcer team lead | View team members, monitor zone coverage, track team citations |

---

## PART B — SYSTEM RESOURCES

### 4. Hardware Requirements

| Hardware / Device | Purpose | Required? |
|-------------------|---------|-----------|
| **Cloud Web Service (Render)** | Host Laravel app, background workers, scheduler | ✅ Yes |
| **Supabase PostgreSQL (Managed)** | Persistent storage for citations, users, payments, GPS logs — hosted separately from app | ✅ Yes |
| **Enforcer Smartphones (Android/iOS)** | Field citation issuance, GPS tracking, camera for evidence | ✅ Yes |
| **Admin Workstations / Laptops** | Dashboard, reports, user management, payment monitoring | ✅ Yes |
| **Cashier Desktop + Receipt Printer** | Walk-in payment recording, receipt printing (5×7 thermal) | ✅ Yes |
| **QR Code Scanner (optional)** | Quick citation lookup at payment counter | ⭕ Optional |
| **SSL Certificate (Let's Encrypt / Render auto + Supabase)** | HTTPS for geolocation, PayMongo webhooks, PCI compliance | ✅ Yes |

---

### 5. Software Requirements

| Software / Technology | Purpose | Required Version / Notes |
|----------------------|---------|--------------------------|
| **PHP** | Backend runtime | 8.2+ (Laravel 11 requirement) |
| **Laravel Framework** | Core MVC, auth, queues, scheduler | 11.x (LTS) |
| **PostgreSQL** | Relational database (via Supabase, run separately) | 15+ (PostGIS for zones, JSONB for metadata) |
| **Composer** | PHP dependency manager | 2.x |
| **Node.js + Vite** | Frontend asset bundling (CSS/JS) | Node 20+, Vite 5+ |
| **MapLibre GL JS** | Interactive maps (zones, tracking, citation location) | 5.24+ (via CDN) |
| **Chart.js** | Dashboard analytics charts | 4.x (auto import) |
| **SimpleSoftwareIO Laravel QrCode** | Citation & payment QR generation | 4.x |
| **PayMongo PHP SDK** | Payment gateway integration (GCash/Maya/Card) | 2.x |
| **Spatie Laravel Activitylog** | Audit trail for citations, payments, users | 4.x |
| **Laravel Sanctum** | API token auth (mobile, webhooks) | 3.x |
| **Supabase Client / pgbouncer** | Connection pooling, auth, realtime (if used) | Latest |
| **Redis (optional)** | Queue worker, caching, rate limiting | 7.x (if scaling) |
| **Git + GitHub** | Version control, CI/CD triggers | Latest |
| **Render.com** | Web service hosting, auto-deploy, SSL, cron, workers | Paid plan for production |
| **Supabase Dashboard** | DB admin, backups, point-in-time recovery, logs | Free/Pro tier |

---

### 6. Database

| Item | Value |
|------|-------|
| **DBMS** | PostgreSQL 15+ (Supabase Managed) |
| **Database Name** | `tems_production` |
| **Hosting** | Supabase Cloud — run separately from the app hosting on Render |
| **Administrator** | Team DevOps (Supabase dashboard + `psql` via pooled connection) |
| **Connection** | `postgresql://user:pass@db.xxx.supabase.co:5432/tems_production?pgbouncer=true` (pooled) / direct port 5432 for migrations |
| **Extensions** | `postgis` (zone polygons, distance queries), `uuid-ossp`, `pg_trgm` (search), `btree_gin` (composite indexes) |

**Important Data:**

| Data / Information | Why Is It Important? |
|--------------------|----------------------|
| **Citations** (citations table) | Core legal record: violation, plate, driver, location, GPS coords (lat/lng), penalty, status, QR token — must be immutable after issuance |
| **Payments** (payments table) | Financial record: amount, method, PayMongo IDs, receipt #, paid_at — audit-critical, PCI-relevant |
| **Users & Roles** (users, roles tables) | Access control: enforcers, admins, cashiers — RBAC gates every action |
| **EnforcerLocation** (enforcer_locations) | Real-time GPS for dispatch & accountability; accuracy_m for precision awareness |
| **Zones & Teams** (zones, teams) | Geographic assignment, zone-radius alerts (PostGIS), team-based reporting |
| **Violation Types** (violation_types) | Master list of offenses, penalties, impoundability — legal reference |
| **Activity Log** (activity_log) | Tamper-evident audit trail for every create/update/delete on citations, payments, users |
| **PayMongo Webhook Events** (storage/logs) | Payment reconciliation, debugging failed transactions |

---

### 7. Network Requirements

☑ **Internet** — Required for all users (cloud-hosted)  
☑ **HTTPS (TLS 1.2+)** — Mandatory for browser geolocation API, PayMongo webhooks, secure cookies  
☑ **Mobile Data (4G/5G)** — Enforcer smartphones need cellular data for GPS + map tiles + citation submission in field  
☑ **Wi-Fi** — Admin/cashier workstations on office LAN/Wi-Fi  
☐ LAN-only — Not applicable (cloud deployment)

**Connection Flow**:  
- Enforcer phone → Mobile Data → HTTPS → Render (app) → Supabase (DB)  
- Admin/Cashier → Office Wi-Fi → HTTPS → Render → Supabase  
- Citizen → Any Internet → HTTPS → Render (public pages)  
- PayMongo Webhooks → HTTPS POST → Render `/paymongo/webhook` (public, no auth)  
- Map tiles (Carto Positron / OpenFreeMap) → CDN over HTTPS  
- Supabase → Render (DB connections via pgbouncer pooler, port 6543)

---

### 8. Deployment / Hosting Environment

☑ **Cloud Server (Render.com) — App Only**  
☑ **Supabase — Database Only (Separate)**  

**Planned Deployment**:
- **Web Service (Render)**: `tems-web` (Laravel, PHP 8.2, `php artisan octane:start --server=swoole --host=0.0.0.0 --port=$PORT`)
- **Background Worker (Render)**: `tems-worker` (`php artisan queue:work --sleep=3 --tries=3 --queue=high,default,low`)
- **Cron Job (Render)**: `tems-scheduler` (`php artisan schedule:run` every minute: overdue citations, webhook retry cleanup, report generation, GPS pruning)
- **Database (Supabase)**: `tems-db` — Primary + read replica; PITR enabled; daily automated backups retained 7 days (free) / 30 days (Pro)
- **Environment Variables** (Render Dashboard → never in repo):
  - `DB_HOST`, `DB_PORT=6543`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (Supabase pooler credentials)
  - `PAYMONGO_SECRET_KEY`, `PAYMONGO_PUBLIC_KEY`, `PAYMONGO_WEBHOOK_SECRET`
  - `APP_KEY`, `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`
  - `MAIL_*`, `MAP_TILE_PROVIDER`
- **Build Command (Render)**: `composer install --no-dev && npm ci && npm run build && php artisan migrate --force`
- **Health Check**: `/up` endpoint (Laravel Pulse or custom)
- **Domains**: `tems.lgu.gov.ph` (custom domain + Cloudflare proxy for DDoS/WAF, DNS pointing to Render)

---

## PART C — SYSTEM ADMINISTRATION

### 9. What Needs to Be Administered?

| Component | What Needs to Be Administered? | Who Will Be Responsible? |
|-----------|--------------------------------|--------------------------|
| **User Accounts** | Create/disable enforcer, admin, cashier accounts; assign roles; reset passwords; enforce MFA for admins | Super Admin / IT |
| **User Permissions** | Manage Spatie roles/permissions: `create citation`, `view reports`, `manage users`, `impounding refer`, `payment record`; audit role changes | Super Admin |
| **Application** | Deploy updates (Render auto-deploy on push); clear caches (`view:cache`, `config:cache`, `route:cache`); run migrations on Supabase; monitor Laravel Pulse / logs | DevOps / Lead Dev |
| **Database (Supabase)** | Backups (Supabase auto-daily + PITR); index optimization; monitor slow queries via Supabase Dashboard; archive old citations (>2 yr) to cold storage; retention policy; manage connection pool (pgbouncer) limits | DevOps / DBA |
| **Server / Hosting (Render)** | Render service health; scaling (CPU/RAM); worker count; cron job logs; SSL cert renewal (auto); domain DNS | DevOps |
| **Network** | Cloudflare WAF rules (rate limit login, block bad bots); PayMongo webhook IP allowlist; CORS for map tiles; enforce HSTS; Supabase IP allowlist for Render IPs | DevOps |
| **Security** | Rotate APP_KEY annually; rotate PayMongo secret keys quarterly; patch PHP/Laravel dependencies monthly (`composer audit`); review activity log for anomalies; enforce strong passwords + MFA | SecOps / Lead Dev |
| **Storage** | `storage/app/public` (citation evidence photos, QR codes) — symlink to `public/storage`; monitor disk usage; offload to S3/Supabase Storage if >10GB; backup evidence nightly | DevOps |
| **Payments / PayMongo** | Monitor webhook success/fail rates; reconcile daily settlements; handle disputed/refunded payments; update webhook secret on rotation | Finance + DevOps |
| **Maps / Geolocation** | Monitor MapLibre/CDN availability; fallback tile provider; accuracy thresholds for GPS (50/150m) | Frontend Lead |
| **Other: Reports** | Schedule monthly revenue/performance PDFs to admin email; ensure report date defaults work | Lead Dev |
| **Supabase-Specific** | Manage Supabase Auth (if used for citizen portal); Row Level Security policies; Realtime subscriptions (if added); Database branching for staging | DevOps |

---

## PART D — SYSTEM MAINTENANCE

### 10. What Needs to Be Maintained?

| Maintenance Activity | Why Is It Necessary? | Frequency |
|----------------------|----------------------|-----------|
| **Database Backup** | Disaster recovery; point-in-time restore for citations/payments; legal retention (Supabase PITR + manual exports) | Daily auto (Supabase PITR) + Weekly manual `pg_dump` offsite + Monthly test restore |
| **Software Update** | Laravel patch releases (security), PHP patches, Composer dependencies — prevent exploits | Monthly (test on staging first) |
| **Security Update** | Base image patches (Render handles), Composer `audit`, rotate secrets, review activity log for privilege escalation | Monthly + immediate for CVEs |
| **User Account Review** | Disable separated employees; audit enforcer active status; remove stale cashier accounts | Quarterly + on HR trigger |
| **System Check** | Health endpoint `/up`; queue worker alive; scheduler ran; PayMongo webhook 200 rate >99%; Render disk >20% free; Supabase disk >20%; SSL expiry >30 days | Daily automated (Laravel Pulse + UptimeRobot + Supabase alerts) |
| **Evidence Photo Cleanup** | Remove orphaned photos (citations deleted/archived); enforce max 5MB/photo | Monthly |
| **PayMongo Reconciliation** | Match webhook `payment.paid` events to `payments` table; investigate mismatches; refund handling | Daily (finance) |
| **GPS Data Pruning** | Trim `enforcer_locations` older than 90 days (keep last known only); reduce Supabase DB size | Monthly via scheduler job |
| **Report Generation Cache** | Pre-compute heavy reports (enforcer performance, revenue by zone) nightly | Nightly via scheduler |
| **Supabase Maintenance** | Monitor pgbouncer pool usage; vacuum/analyze; review slow query dashboard; extension updates | Weekly |
| **Other: SSL / Domain** | Auto-renew (Render/Cloudflare); verify CAA records; HSTS preload | Yearly check |

---

## PART E — WHAT COULD GO WRONG?

### 11. Identify Possible System Problems

| # | Possible Problem | Possible Cause | Possible Impact |
|---|------------------|----------------|-----------------|
| 1 | **PayMongo webhook fails silently** | Render deploy downtime / network blip / invalid signature / payload change | Citation stays "Issued" despite payment; citizen sees paid but system doesn't; revenue under-reported; manual reconciliation needed |
| 2 | **Enforcer GPS shows 1–2 km off / "Tracking paused" stuck** | Desktop testing (no GPS), indoor GPS, cold-start fix sent before convergence, JS error killing watcher | Admin dispatch sees wrong location; trust in tracking erodes; enforcer can't prove presence |
| 3 | **Citation QR code unreadable / payment page 404** | `APP_URL` mismatch, `public/storage` symlink broken, QR token tampered, citation deleted | Citizen cannot pay; violator claims "system broken"; revenue loss; support tickets spike |
| 4 | **Report pages crash with "Call to member function format() on null"** | Date filter default misused (`$request->date()` returns null), Carbon parse fails | Admins cannot view revenue/citation/enforcer reports; decision-making blocked |
| 5 | **Database disk full / slow queries (Supabase)** | Evidence photos accumulate, `enforcer_locations` never pruned, missing indexes; pgbouncer pool exhaustion | App slowdown, 500 errors, backup fails, new citations reject; connection pool exhausted |
| 6 | **PayMongo secret key compromised / rotated without update** | Key leaked in logs/repo, or rotated quarterly but Render env var not updated | All webhooks fail (signature mismatch); payments unrecorded; emergency hotfix needed |
| 7 | **Citizen portal shows wrong violation / amount** | `violation_type_id` FK mismatch, `penalty_amount` cached stale, cashier recorded wrong amount | Citizen disputes; legal liability; trust loss |
| 8 | **Enforcer cannot issue citation (validation errors)** | `StoreCitationRequest` rules too strict, missing `violation_type` active filter | Field work stops; paper fallback used; data gaps |
| 9 | **Printed ticket/receipt overflows 5×7 page** | CSS `@page` rules broken, QR too large, footer cut off | Unprofessional receipts; legal document rejected; reprint waste |
| 10 | **Scheduler / queue stops processing** | Render worker crashes, memory leak in `queue:work`, deadlock on `enforcer_locations` upsert; pgbouncer connection leak | Overdue citations not auto-updated, webhook retries stall, reports not generated |
| 11 | **Supabase connection pool exhaustion** | Too many concurrent workers + scheduler + web requests exceeding pgbouncer `max_client_conn` | New requests queue/reject; 503 errors; enforcers can't save citations in field |
| 12 | **PostGIS extension missing / version mismatch after Supabase upgrade** | Supabase platform upgrade changes PostGIS version; zone radius queries break | Zone assignment fails; "inside zone" logic incorrect; dispatch misrouted |

---

## PART F — SYSTEM ADMINISTRATOR'S RESPONSIBILITIES

### 12. If You Were the System Administrator...

1. **Monitor & ensure PayMongo webhook health daily** — check `/paymongo/webhook` 200 rate >99%, reconcile `payments` table vs PayMongo dashboard, alert on >1% failure, rotate `PAYMONGO_WEBHOOK_SECRET` quarterly and update Render env var within 1 hour.

2. **Guarantee enforcer GPS reliability** — verify dashboard toggle works on real phones (not desktop), enforce accuracy threshold (≤75m before send), display accuracy radius on admin tracking map, prune stale `enforcer_locations` monthly, investigate "Tracking paused" reports within 1 hour.

3. **Run & verify daily automated checks** — Laravel Pulse health, queue worker count, scheduler last run, DB backup success (test restore monthly), Render disk >20%, Supabase disk >20%, SSL cert >30 days, PayMongo settlement matched, pgbouncer pool usage <80%.

4. **Maintain audit integrity** — ensure `activity_log` captures every citation create/update, payment status change, user role change; immutable (append-only); review weekly for anomalies (bulk deletes, privilege escalation).

5. **Manage deployment pipeline & rollback** — Render auto-deploy on `master` push; maintain `staging` branch for QA; one-click rollback via Render dashboard; run `php artisan migrate --force` on Supabase only after backup; zero-downtime deploy with Octane.

6. **Enforce security hygiene** — monthly `composer audit` + `npm audit`; rotate `APP_KEY` annually; enforce MFA for all admin accounts; Cloudflare WAF rate limits on `/login`, `/paymongo/webhook`; Supabase IP allowlist for Render egress IPs; quarterly penetration test (OWASP Top 10).

7. **Maintain print & legal compliance** — verify 5×7 ticket/receipt layout monthly (QR visible, footer intact, PAID stamp on paid); ensure citation_number unique & sequential; receipt_number separate series; archive PDFs of paid citations for 7 years.

---

## PART G — REFLECTION

### 13. What did you realize about your Capstone Project after performing this activity?
TEMS is not just a "citation app" — it's a **payment-processing, location-tracking, legal-record system** with real financial and legal consequences. Every component (webhook idempotency, GPS accuracy, QR immutability, print layout, audit log) is a potential failure point that affects citizens, enforcers, and revenue. Administration is not an afterthought; it shapes architecture (e.g., separate `EnforcerLocation` table for GPS pruning, `accuracy_m` column for map circles, `activity_log` for legal defense). The "simple" citation form hides a chain: validation → DB transaction → evidence upload → QR generation → PayMongo checkout → webhook → payment confirmation → receipt print → status update — each link must be observable and recoverable. **Separating the database (Supabase) from the app (Render) adds operational complexity** — two dashboards, two billing cycles, connection pooling tuning, cross-region latency — but gives independent scaling, PITR, and PostGIS without managing Postgres ourselves.

### 14. Which part of your system do you think will require the most administration or maintenance? Why?
**Payments + Webhooks + PayMongo integration**. Reasons: (a) External dependency — we don't control PayMongo uptime, payload format, or settlement timing; (b) Financial data demands zero-loss — every failed webhook = manual reconciliation; (c) Security surface — secrets rotation, PCI-DSS awareness, refund/dispute handling; (d) Testing difficulty — sandbox vs live behave differently; (e) Citizen-facing — any bug = public complaint + revenue loss. Close second: **GPS/Tracking** — device fragmentation (Android/iOS, desktop, indoor), battery/permission UX, accuracy visualization, data volume pruning. Third: **Supabase connection pooling & PostGIS** — pgbouncer limits, vacuum/analyze, extension upgrades, cross-region latency to Render.

---

## FINAL CHECK

- [x] System Users
- [x] Hardware
- [x] Software
- [x] Database
- [x] Network Requirements
- [x] Deployment/Hosting
- [x] Administration Requirements
- [x] Maintenance Requirements
- [x] Possible Problems
- [x] Administrator Responsibilities
- [x] Reflection