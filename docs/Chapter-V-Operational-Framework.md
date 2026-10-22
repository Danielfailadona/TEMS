# Chapter V: Operational Framework

## 5.1 Input-Process-Output (IPO) Model Overview

The Traffic Enforcement Management System (TEMS) operates on the **Input-Process-Output (IPO)** model, a fundamental framework that describes how data flows through the system. This model delineates how inputs—encompassing data and signals from enforcers, vehicle owners, administrators, and external sources—undergo systematic processes (validation, business logic, database transactions, computations) to yield meaningful outputs such as citations, impounding records, payment receipts, appeal decisions, and analytical reports.

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                            TEMS IPO MODEL                                        │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│    ┌──────────────┐     ┌──────────────────────────────────────────────────┐    │
│    │   INPUTS     │────▶│              PROCESSES                           │    │
│    │              │     │  • Validation & Sanitization                       │    │
│    │ • Enforcer   │     │  • Business Logic & Rules                          │    │
│    │   Actions    │     │  • Database Transactions                           │    │
│    │ • Owner      │     │  • State Transitions                               │    │
│    │   Requests   │     │  • Notifications & Events                          │    │
│    │ • External   │     │  • Report Generation                               │    │
│    │   APIs       │     │  • Audit & Archiving                               │    │
│    │ • Scheduled  │     │                                                    │    │
│    │   Jobs       │     │                                                    │    │
│    └──────────────┘     └──────────────────────────────────────────────────┘    │
│                                 │                                                │
│                                 ▼                                                │
│    ┌──────────────────────────────────────────────────────────────────────────┐  │
│    │                            OUTPUTS                                       │  │
│    │  • Citations & Impounding Records                                        │  │
│    │  • Payment Receipts & QR Codes                                           │  │
│    │  • Appeal Decisions                                                      │  │
│    │  • Clamping Records                                                      │  │
│    │  • Analytical Dashboards & Reports                                       │  │
│    │  • Notifications (Email, SMS, In-App)                                    │  │
│    │  • Audit Trails & Archives                                               │  │
│    │  • Printable Documents (Citations, Receipts, Release Orders)            │  │
│    └──────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 5.2 Inputs

### 5.2.1 Data Sources

| Source | Description | Examples |
|--------|-------------|----------|
| **Enforcers (Field Officers)** | Primary data entry via mobile/web | Citation issuance, impounding creation, clamping records, evidence photos, GPS coordinates |
| **Vehicle Owners** | Self-service portal interactions | Appeal submissions, online payments, citation lookups, profile updates |
| **Cashiers/Administrators** | Back-office operations | Payment recording (cash/online), appeal review, team management, zone configuration |
| **Super Admins** | System configuration | User management, role assignment, violation type CRUD, fee schedules, system settings |
| **External Systems** | Integration points | PayMongo (online payments), Supabase Storage (evidence), OSM Nominatim (geocoding) |
| **Scheduled Jobs** | Automated background processes | Citation overdue marking, notification dispatch, report generation, cleanup tasks |

### 5.2.2 Data Types

| Category | Data Types | Validation Rules |
|----------|------------|------------------|
| **Identification** | Citation numbers (CIT-YYYYMMDD-XXXXXX), Notice numbers (CLP-/IMP-YYYYMMDD-XXXXXX), Receipt numbers (RCP-YYYYMMDDHHMMSS-XXXX) | Auto-generated, unique, format-validated |
| **Vehicle** | Plate number (alphanumeric, max 20), Make/Model/Type/Color (strings), GPS coordinates (decimal, precision 7) | Required: plate, location; Plate format: alphanumeric |
| **Personnel** | Enforcer/Owner names, emails, roles, badge IDs, contact numbers | Role-based: Enforcer, Cashier, Admin, Owner, FrontDesk; Email unique |
| **Violations** | Code, name, description, penalty amount (decimal), is_active, is_impoundable, is_clampable | Code unique; Penalty ≥ 0; Boolean flags for workflow routing |
| **Evidence** | Image files (JPEG/PNG/WebP, max 5MB), file paths (Supabase Storage), original filenames | Required for citation/impounding/clamping; MIME type validation |
| **Payments** | Amount (decimal), method (Cash, GCash, PayMaya, Card, Bank Transfer, Other), reference number, notes | Amount > 0; Method enum; Reference optional |
| **Appeals** | Reason, description, status (Submitted/Under Review/Approved/Rejected), adjusted amount, decision notes | Status enum; Adjusted amount ≥ 0 if provided |
| **Geospatial** | Latitude/Longitude (decimal ±90/±180), radius (meters), zone polygons (GeoJSON) | Precision 7; Radius > 0; Valid coordinate bounds |
| **Temporal** | Issued/Impounded/Clamped/Paid/Released timestamps, due dates, grace periods | Auto-set on creation; Due date = issued + config days; Grace = impounded + config hours |

---

## 5.3 Processes

### 5.3.1 Processing Logic & Business Rules

#### A. Citation Issuance Workflow
```
Enforcer creates citation
       │
       ▼
Validate violation type (active, impoundable/clampable flags)
       │
       ▼
Store citation (status: Issued) + evidence photos
       │
       ├─▶ If violation.is_impoundable = true
       │      Create ImpoundingRecord (status: Impounded, notice: IMP-...)
       │      Reuse first evidence photo
       │      Set citation status = Impounded
       │      Apply fee schedule from config (towing + storage/day + admin)
       │
       └─▶ Else if violation.is_clampable = true (not impoundable)
              Set citation status = Clamped
              (No auto-record; requires separate clamping action)
```

#### B. Impounding Lifecycle
```
Impounded ──(grace period: 24h default)──▶ AwaitingPayment
       │                                        │
       │                                        ▼
       │                              Payment recorded
       │                                        │
       │                                        ▼
       │                              Status: Paid, paid_at set
       │                                        │
       │                                        ▼
       │                              FrontDesk: Queue Release
       │                                        │
       │                                        ▼
       │                              Status: WaitingRelease
       │                                        │
       │                                        ▼
       │                              FrontDesk: Process Release
       │                                        │
       │                                        ▼
       └──────────────────────────────────▶ Status: Released
                                              Citation status: Released (if linked)
                                              VehicleRelease record created
```

#### C. Clamping Workflow (Legacy/Parallel)
```
Citation (Overdue/Issued, clampable) ──▶ Refer to Impounding
       │                                          │
       ▼                                          ▼
Create ClampingRecord (status: AwaitingPayment)  Create ImpoundingRecord
       │                                          │
       ▼                                          ▼
Payment recorded ──▶ Paid ──▶ WaitingRelease ──▶ Released
       │
       ▼
Citation status = Paid (if unpaid)
```

#### D. Appeal Processing
```
Owner submits appeal (via tokenized public URL)
       │
       ▼
Validate: citation payable, no existing appeal, not paid
       │
       ▼
Create Appeal (status: Submitted, guest or authenticated)
       │
       ▼
Staff reviews ──▶ Approved
       │                 │
       │                 ▼
       │           If citation unpaid:
       │              Apply 15% discount (default) or custom adjusted_amount
       │              Store original_amount + adjusted_amount on appeal
       │              Update citation.penalty_amount = adjusted_amount
       │
       └──▶ Rejected
                  │
                  ▼
           Citation unchanged
           Appeal status = Rejected
```

#### E. Payment Processing
```
Cashier selects category (Citation / Clamping / Impounding)
       │
       ▼
Lookup record (citation/clamping/impounding)
       │
       ▼
Validate: not already paid, record payable
       │
       ▼
Create Payment record (receipt: RCP-..., paid_at = now)
       │
       ├─▶ Citation: status = Paid; archive; notify enforcer
       │
       ├─▶ Clamping: status = Paid; clamping_fee = amount; citation = Paid (if unpaid)
       │
       └─▶ Impounding: status = Paid; paid_at = now; citation UNCHANGED (2-payment flow)
```

#### F. Team & Zone Management
```
Admin creates/updates Team
       │
       ▼
Assign members (Enforcers only) + leader
       │
       ▼
Assign zones (map click toggle → zones[] sync)
       │
       ▼
Sync members (pivot) + zones (team_id FK) + leader_id
       │
       ▼
Dashboard KPIs: active teams, members, citations/month per team
```

---

### 5.3.2 Sequence of Operations

| Phase | Operation | Trigger | Key Validations |
|-------|-----------|---------|-----------------|
| **1. Setup** | Seed violation types, zones, users, teams, fee config | Migration + Seeder | Unique codes, valid enums, fee schedules |
| **2. Field Action** | Enforcer issues citation | Enforcer POST /citations | Violation active, evidence required, plate format |
| **3. Auto-Routing** | Impounding/Clamping auto-creation | Citation store() | `is_impoundable` → Impounded + ImpoundingRecord; `is_clampable` → Clamped |
| **4. Citizen Action** | Owner views/pays/appeals | Public tokenized URL | Token HMAC match, citation payable, no existing appeal |
| **5. Appeal Review** | Staff decides | PATCH /appeals/{id} | Status enum, adjusted_amount ≥ 0, 15% default if blank on approve |
| **6. Payment** | Cashier records payment | POST /payments | Category + record ID, amount > 0, method enum, not already paid |
| **7. Impounding Flow** | Mark paid → Queue release → Process release | ImpoundingController actions | Status transitions enforced by policies |
| **8. Overdue Processing** | Scheduled job (daily) | `CitationNumberService::markOverdueCitations()` | Issued + due_date < now → Overdue |
| **9. Reporting** | Dashboard / Reports | GET /dashboard, /reports/* | Role-based filters, date ranges, status grouping |
| **10. Archiving** | Every state change | Model events / Controller actions | Snapshot = model->toArray(); reason = action description |

---

## 5.4 Outputs

### 5.4.1 Output Types

| Output Category | Specific Outputs | Description |
|-----------------|------------------|-------------|
| **Enforcement Records** | Citations, Impounding Records, Clamping Records, Vehicle Releases | Core enforcement documents with unique IDs, status, timestamps |
| **Financial Documents** | Payment Receipts (QR-coded), Fee Breakdowns, Refund Records | Printable, QR-coded for verification, audit-ready |
| **Adjudication** | Appeal Decisions (Approved/Rejected), Adjusted Penalty Amounts | Original vs. Final amounts, decision notes, reviewer info |
| **Operational Reports** | Dashboard KPIs, Citation Trends, Revenue by Month, Appeals by Status, Top Violations, Team Performance | Filterable by date, status, enforcer, team; exportable |
| **Notifications** | In-app alerts, Email/SMS (configurable), QR handoff for violators | Real-time for enforcers/cashiers; scheduled digests for admins |
| **Audit & Compliance** | Archive Snapshots (every state change), Activity Logs (Spatie), Deleted Entity Logs | Immutable snapshots, user attribution, timestamps |
| **Printable Forms** | Citation Ticket (QR + details), Impounding Notice, Clamping Notice, Release Order, Payment Receipt | PDF-ready HTML views, QR codes for public verification |

---

### 5.4.2 Format & Presentation

| Output | Format | Styling/Structure |
|--------|--------|-------------------|
| **Web UI** | HTML5 + Blade templates, Tailwind-like CSS (custom `itevcms` variables), Alpine.js / Vanilla JS | Card-based layout, status badges (tag-green/amber/red/gray), responsive tables, modal forms |
| **Print/PDF** | HTML → Browser Print → PDF | A4-optimized, `@media print` styles, QR codes (SVG), official headers/footers |
| **QR Codes** | SVG (inline) + Data URI | 280px citation QR, 160px handoff QR, encodes public URL with HMAC token |
| **API/JSON** | RESTful (future) | Resource collections, single resources, paginated, filterable |
| **Charts** | Chart.js (dashboard) | Monthly citations/revenue/appeals bars, top violations horizontal bars |

---

### 5.4.3 Delivery Methods

| Output | Delivery Channel | Access Control |
|--------|------------------|----------------|
| **Enforcement Records** | Web UI (role-based), Direct links (tokenized for public) | Role: Enforcer/Admin/Cashier/Owner; Public: tokenized only |
| **Payment Receipts** | Web view, Print modal, QR code on citation | Cashier/Admin: all; Owner: own citations; Enforcer: issued citations |
| **Appeal Decisions** | Web view (staff), Public portal (owner via token) | Staff: all appeals; Owner: own appeals |
| **Reports/Dashboards** | Web UI (authenticated) | Role-based: Admin=all, Enforcer=own, Cashier=payments, FrontDesk=impounding |
| **Notifications** | In-app (real-time), Email (configurable), SMS (future) | Per-user preferences; critical alerts always delivered |
| **Printable Documents** | Browser Print → PDF/Save | Same as web view access; print-specific routes (`/citations/{id}/print`) |
| **Archives/Logs** | Web UI (Admin only), DB queries | Admin only; immutable, append-only |

---

## 5.5 Summary

The TEMS operational framework implements a **closed-loop IPO cycle** where every enforcement action generates structured inputs, undergoes deterministic state-machine processes with full auditability, and produces verifiable outputs accessible to the appropriate stakeholders. The system enforces data integrity through:

- **Database-level constraints** (FKs, unique indexes, enum casts)
- **Application-level validation** (FormRequests, Policies, Enum casting)
- **State-machine enforcement** (Status enums + Policy gates)
- **Immutable audit trail** (Archive snapshots on every transition)
- **Tokenized public access** (HMAC-SHA256 for citation lookup/payment/appeal)

This architecture ensures that from the moment an enforcer issues a citation to the final release of an impounded vehicle, every data transformation is traceable, validated, and delivers the correct output to the right stakeholder at the right time.