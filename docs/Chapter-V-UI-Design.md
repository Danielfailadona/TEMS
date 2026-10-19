# Chapter V: User Interface Design

## 5.6 User Interface Design Overview

This section documents the complete user interface inventory for the Traffic Enforcement Management System (TEMS), organized by user role. Each screen is mapped to its functional purpose, key components, and navigation flow.

---

## 5.6.1 Admin Side Screens

### 5.6.1.1 Dashboard & Overview

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Admin Dashboard | /dashboard | System-wide KPIs, trends, recent activity | KPI cards (total citations, unpaid, revenue, active clamps, pending appeals), week-over-week trend charts, citations by month, revenue by month, appeals by month, top violation types, recent activity feed |
| Admin Dashboard (Front Desk) | /dashboard (role: FrontDesk) | Impounding-focused dashboard | Impounding queue stats, pending releases, recent impounding actions |

### 5.6.1.2 User Management

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| User Management | /users | CRUD for all system users | Searchable table (name, email, role, status), pagination, role filter, status toggle, create/edit modal (name, email, role, password, active), role assignment (SuperAdmin, Administrator, Enforcer, Cashier, FrontDesk, VehicleOwner) |
| Add User | /users/create | Create new user account | Form: name, email, role select, password, confirm password, active checkbox |
| Edit User | /users/{id}/edit | Modify existing user | Pre-filled form, role change, password reset option, active toggle |

### 5.6.1.3 Zone Management

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Zones List | /zones | View all enforcement zones | Searchable table (name, center coords, radius, team assignment, status), map preview toggle, pagination |
| Add Zone | /zones/create | Create new enforcement zone | Form: name, address (geocoded), center lat/lng (map click or manual), radius (meters), team assignment, active toggle; MapLibre GL map with circle editor |
| Edit Zone | /zones/{id}/edit | Modify existing zone | Pre-filled form, map with existing circle, radius adjustment, team reassignment, status toggle |
| Zone Map Viewer | /zones/{id} (viewer) | Public/readonly zone map | MapLibre map with zone circle, detail overlay on click (name, radius, area, team, status, coordinates) |

### 5.6.1.4 Team Management

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Teams List | /teams | View all enforcement teams | Cards/grid: team name, member count, zone count, status badge, citation count (month), quick actions (edit, toggle, delete) |
| Add Team | /teams/create | Create new team | Form: name, description, leader select (Enforcers), members multi-select (Enforcers), zone assignment (map click toggle), active toggle |
| Edit Team | /teams/{id}/edit | Modify team details | Tabs: Basic Info (name, description, lead radio from members), Members (searchable list with checkboxes, avatar stack preview, lead selection), Settings (active toggle), Zone Coverage (MapLibre click-to-assign, assigned zone chips, hidden zones[] sync), Danger Zone (delete modal) |

### 5.6.1.5 GPS Tracking

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| GPS Tracking | /gps-tracking | Real-time enforcer locations | MapLibre map with enforcer markers (last known position, timestamp, accuracy), team filter, time range selector, auto-refresh toggle |

### 5.6.1.6 Clamping Management

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Clamping List | /clamping | All clamping records | Table: plate, notice #, violation, officer, clamped at, status, evidence thumb, actions (view, payment, queue release, process release) |
| Clamping View | /clamping/{id} | Detail + payment + release flow | Header: plate, notice #, status badge; Cards: clamping details, citation details, fee breakdown, evidence, payment record modal, queue release button, process release modal, print release order |
| Clamping Request | /clamping-request | Incoming clamp requests | Table: requestor, plate, location, status, created at, actions (approve/deny) |
| Clamping Request View | /clamping-request/{id} | Review individual request | Request details, citation linkage, approve/deny actions with notes |

### 5.6.1.7 Citation Management

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Citations List | /citations | All citations | Filterable table (search, status, date range): citation #, violation, plate, amount, issued, status badge, view link, impounding link (truck icon if linked) |
| Citation View | /citations/{id} | Full citation detail | Header: citation #, plate, status badge, QR code; Cards: violation details, vehicle/driver info, location map, evidence gallery, payment record (or payment buttons), appeal status, impounding link (or refer button if eligible) |
| Issue Citation | /citations/create | Enforcer creates citation | Form: plate, vehicle details, driver info, violation select (data-impoundable/clampable), location + GPS, notes, evidence capture (camera/gallery, multi); Conditional impounding section (notes + evidence reuse note) + LTO eligibility panel |

---

### 5.6.1.8 Impounding Management

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Impounding List | /impounding | All impounding records | Table: plate, notice #, violation, officer, impounded at, status, evidence thumb, actions (view, mark paid, queue release, process release) |
| Impounding View | /impounding/{id} | Detail + payment + release flow | Header: plate, notice #, status badge; Cards: impounding details, citation details, fee breakdown, evidence, payment record modal, queue release button, process release modal, print release order |
| Impounding Create | /impounding/create | Manual impounding (officer-initiated) | Form: plate lookup (auto-fetch citation), location, notes, evidence photo (required); Creates ImpoundingRecord with IMP- notice; Fee schedule from config |

---

### 5.6.1.9 Appeals Management

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Appeals List | /appeals | All appeals | Filterable table (status, date range): citation #, appellant, reason, status badge, submitted at, actions (view, approve/reject) |
| Appeal View | /appeals/{id} | Full appeal detail | Header: appeal #, citation #, status badge; Cards: appellant info (guest/auth), reason, description, original/final amounts, decision notes, approve/reject buttons with adjusted amount input |
| Appeal Review | /appeals/{id}/edit | Staff decision | Form: status select (Under Review/Approved/Rejected), adjusted amount (prefilled with 15% discount), decision notes; On approve: applies discount to citation penalty |

---

### 5.6.1.10 Audit & Archives

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Audit Logs | /audit-logs | System-wide activity trail | Filterable table (date range, user, action, model): user, action, model, model ID, timestamp, IP, diff viewer modal |
| Archives List | /archives | Archived entity snapshots | Filterable (type, user, date): archivable type, ID, archived by, archived at, reason, view snapshot modal |
| Archive Print | /archives/{id}/print | Printable archive snapshot | Clean print layout: entity type, ID, reason, archived by/at, formatted JSON snapshot |

---

## 5.6.2 Front Desk Side Screens

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Front Desk Dashboard | /frontdesk | Impounding queue overview | Stats cards: impounded, awaiting payment, waiting release; Table: plate, notice #, status, impounded at, officer, actions (view, payment, queue release, process release) |
| Front Desk Impound | /frontdesk/impounding | Impounding management | Same as admin impounding but scoped to front desk permissions (mark paid, queue release, process release, print release) |
| Front Desk Lookup | /frontdesk/lookup | Citation/Plate lookup | Search by citation # or plate; Results: citation details, linked impounding/clamping, payment status, quick actions |

---

## 5.6.3 Enforcer Side Screens

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Enforcer Dashboard | /dashboard (role: Enforcer) | Personal KPIs | My citations (issued, unpaid), active clamps, pending appeals, quick actions (issue citation, view zones) |
| My Zone | /enforcer/zones | Assigned zones map | MapLibre with assigned zone circles, click for detail, GPS current location button |
| Enforcer Impound | /enforcer/impounding | Create impounding records | Form: plate lookup (auto-fetch citation), location, notes, evidence photo (required); Creates ImpoundingRecord with IMP- notice |
| Enforcer Impounding List | /enforcer/impounding/list | View own impounding records | Filterable table: notice #, plate, violation, impounded at, status, actions (view, mark paid, queue release) |
| Enforcer Impounding View | /enforcer/impounding/{id} | Detail + payment + release flow | Header: plate, notice #, status badge; Cards: impounding details, citation details, fee breakdown, evidence, payment record modal, queue release button, process release modal |
| Enforcer Clamping | /enforcer/clamping | Record clamping | Form: plate, location, notes, evidence photo (required); Creates ClampingRecord |
| Enforcer Clamping Request | /enforcer/clamping-requests | Submit clamp requests | Form: plate, location, notes; Creates request for admin approval |
| Enforcer Impounding Appeals | /enforcer/appeals (role: Enforcer) | View/respond to appeals on own citations | List: citation #, appellant, status, submitted at; View detail, approve/reject with adjusted amount |
| Enforcer Archives | /enforcer/archives | Personal archive view | Filterable list of own citations/clampings/impoundings with snapshots |
| Citation Enforcer | /citations/{id}/handoff | QR handoff to violator | Large QR code, citation summary, Show to violator instruction |

---

## 5.6.4 Cashier Side Screens

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Cashier Dashboard | /dashboard (role: Cashier) | Payment queue | Pending citations/clampings/impoundings tabs; Stats: today's revenue, pending count |
| Cashier Payment | /payments/create | Record payment | Lookup (citation/clamping/impounding # or plate), category tabs, amount (auto-filled: citation penalty / clamping fee / impounding total fees), method select, reference, notes; Creates Payment + updates status |
| Cashier Impound | /payments/create?category=impounding | Impounding-specific payment | Same as payment but pre-filtered to impounding category |

---

## 5.6.5 Citizen Side Screens

| Screen | Route | Purpose | Key Components |
|--------|-------|---------|----------------|
| Citizen Ticket Lookup | /cite/{id}/{token} | Public citation view | Citation details, violation, amount, status, evidence; Buttons: Pay Online, Submit Appeal (if payable) |
| Citizen Ticket View | /cite/{id}/{token}/ticket | Printable citation ticket | QR code, citation details, violation, amount, due date, payment instructions |
| Citizen Appeal Submit | POST /cite/{id}/{token}/appeal | Guest appeal form | Guest name, contact, reason, description; Creates Appeal (submitted_by=null, guest_name/contact) |
| Citizen Payment Checkout | /cite/{id}/{token}/checkout | Online payment (PayMongo) | Amount, method select (GCash, PayMaya, Card), redirect to PayMongo, callback handling |
| Citizen Report Parking | /citizen/report | Report violation | Form: plate, location, violation type, description, evidence photos; Creates ClampingRequest for review |
| Citizen Report Track | /citizen/reports | Track own reports | List: report #, plate, location, status, submitted at, view detail |
| Citizen Report Ticket | /citizen/reports/{id} | View report detail | Report details, status timeline, admin response |

---

## 5.6.6 Cross-Cutting UI Patterns

### Navigation & Layout
- **Top Navbar**: Logo, role-based menu links, user dropdown (profile, logout)
- **Sidebar** (Admin/Enforcer): Collapsible, role-filtered navigation groups
- **Breadcrumbs**: Context-aware on all detail/edit pages
- **Sticky Save Bar** (edit forms): Cancel / Submit fixed at bottom on mobile

### Forms & Validation
- **Inline Validation**: @error Blade directives, is-invalid classes, real-time JS for evidence preview
- **File Upload**: Camera/gallery capture, multi-file, preview thumbnails, remove buttons, MIME/size validation
- **Geolocation**: Get Current Location button (navigator.geolocation), lat/lng inputs
- **Map Integration**: MapLibre GL (openfreemap.org style), click-to-set coordinates, circle editor (radius), marker clustering

### Status & Badges
- **Citation Status**: Issued (gray), Overdue (amber), Clamped (red), Impounded (red), Paid (green), Released (green)
- **Impounding Status**: Impounded (red), AwaitingPayment (amber), Paid (blue), WaitingRelease (yellow), Released (green)
- **Clamping Status**: AwaitingPayment (amber), Paid (blue), WaitingRelease (yellow), Released (green)
- **Appeal Status**: Submitted (gray), Under Review (gray), Approved (green), Rejected (red)

### Modals & Confirmations
- **Bootstrap 5 Modals**: Payment, release, delete confirm, zone assignment
- **SweetAlert-style** (custom): Success/error toasts, action confirmations
- **Print Modals**: @media print stylesheets, @push('print-styles') for print-specific CSS

### Responsive Breakpoints
- **Mobile (< 576px)**: Stacked cards, full-width modals, sticky save bar, simplified tables (card view)
- **Tablet (576px-991px)**: 2-column grids, collapsible sidebar
- **Desktop (>= 992px)**: Full sidebar, multi-column layouts, hover interactions

---

## 5.6.7 Screen Flow Diagrams

### Citation Issuance Flow
Enforcer Dashboard -> Issue Citation Form -> Violation impoundable? Yes -> Show Impounding Panel -> Submit -> Creates Citation + ImpoundingRecord
Violation impoundable? No, clampable? Yes -> Set Clamped status -> Submit -> Creates Citation
Violation impoundable? No, clampable? No -> Submit -> Creates Citation (Issued)

### Impounding Lifecycle Flow
Impounding Created (Impounded) -> Grace Period: 24h -> Cashier Records Payment -> AwaitingPayment -> Paid
FrontDesk: Queue Release -> WaitingRelease -> FrontDesk: Process Release -> Released
Citation Status -> Released (if linked)

### Appeal Flow
Citizen: Lookup Citation (token) -> Citation Payable? No -> Cannot appeal
Citation Payable? Yes -> Submit Appeal Form -> Appeal Created (Submitted)
Staff Review -> Approved -> 15% Discount Applied -> Citation Penalty Updated
Staff Review -> Rejected -> No Penalty Change

---

## 5.6.8 Accessibility & Internationalization

- **Semantic HTML**: Proper heading hierarchy, landmarks, ARIA labels
- **Color Contrast**: WCAG AA compliant (status badges, text on backgrounds)
- **Keyboard Navigation**: Focus rings, tab order, modal focus trap
- **Screen Readers**: aria-label, aria-describedby, live regions for toasts
- **RTL Ready**: CSS logical properties (margin-inline, padding-inline)
- **Language**: English primary; Blade @lang() ready for Filipino translation

---

## 5.6.9 Asset Pipeline

| Asset Type | Tool | Output |
|------------|------|--------|
| CSS | Vite + PostCSS (autoprefixer) | public/build/assets/*.css (hashed) |
| JS | Vite (ES modules) | public/build/assets/*.js (hashed) |
| Images | Vite (sharp) | public/build/assets/*.{webp,avif} |
| Fonts | Local (Inter, Material Symbols) | Self-hosted, preloaded |
| Icons | Material Symbols Outlined + Bootstrap Icons | Ligature + SVG |

---

## 5.6.10 Future UI Enhancements

| Area | Planned Improvement |
|------|---------------------|
| Real-time | Laravel Echo + Pusher for live citation/impounding updates |
| Offline | Service Worker for enforcer citation creation in poor connectivity |
| PWA | Installable enforcer app with camera/geolocation permissions |
| Dark Mode | CSS custom properties toggle, prefers-color-scheme |
| Accessibility Audit | axe-core CI integration, automated regression tests |
| Design System | Component library (Button, Card, Modal, Table, FormField) in Vue/Alpine |

---

*End of Chapter V: User Interface Design