# Admin Dashboard KPI Colour Coding — What the Internet Recommends

Reference document. Captures the industry-standard guidance for colour-coding KPI
status indicators on dashboards (the "Needs attention" cards on `/dashboard`),
why our current rule differs, and how we could adopt the recommended model later.

> Companion to `documents/AdminUI.md` (which records what we actually
> implemented) and `documents/FrontDeskChangesRequest.md` (Admin redesign change
> request). This file is the research/reference half.

- Date compiled: 2026-10-11
- Status: Reference (implementation is count-based; see "Our current rule").

---

## 1. The core recommendation: RAG (Red–Amber–Green) status

Industry guidance converges on a three-level traffic-light status system:

| Level | Meaning | Action |
|---|---|---|
| **Green** | On target / acceptable / healthy | No action |
| **Amber** (yellow) | Caution / at risk / warning | Monitor; action may be needed |
| **Red** | Critical / breached / urgent | Action required / escalate |

Rules that accompany it:

1. **Use concrete thresholds per KPI.** A status is only meaningful relative to a
   defined target or tolerance band — never a naked "> 0".
2. **Amber = within tolerance; Red = beyond it.** Typical practice is a tolerance
   band around the target (e.g. within ~10% of target = amber, beyond = red).
3. **When several items roll up to one indicator, show the highest-attention
   colour** (any red → red; else any amber → amber; else green).
4. **Grey / neutral = insufficient data** (no reading) — distinct from green.
5. **Colour means status, not decoration** — use the same colours consistently
   for the same meaning everywhere.

### Why our current rule differs

Our implemented rule is a simplified, **whole-set count threshold**:

- Green `= 0`, Amber `= below cutoff`, Red `= >= cutoff`
  (cutoffs: Clamps `4`, Appeals `10`, Unpaid Citations `50`).

This is a valid target-style threshold (target = 0 backlog), but it is *not*
time/SLA-based and the cutoffs are operational placeholders, not derived targets.

---

## 2. The queue/SLA recommendation (the "proper" model for work backlogs)

For anything that behaves like a **work queue**, the recommended approach is
**time/SLA-based**, not raw counts:

- **Jira Service Management:** green while within SLA; amber as the SLA nears
  breach (e.g. < 30 min remaining); red once breached. A green tick / red cross
  icon accompanies the colour.
- **Tungsten Automation TotalAgility (work-queue SLA):** configurable
  thresholds — green until the amber threshold, amber while "due soon", red once
  past the expected finish (overdue).
- **ServiceNow aging-ticket practice:** bucket by age (`0–3`, `4–7`, `>7` days)
  and escalate items in the oldest bucket; prioritise the oldest first.
- **Accounts-receivable (aging) convention:** bucket by age (`0–30`, `31–60`,
  `>60` days) and track the amount/count in each older bucket.

### The aggregation trap (important)

A single item's timestamp cannot represent a set of items:

- colour by the **newest** item (`max(created_at)`) → a fresh item **masks** older
  ones → the KPI reads green forever;
- colour by the **oldest** item (`min(created_at)`) → ancient items dominate → the
  KPI reads red forever.

The recommended fix is to aggregate across **all** items — e.g. count the items
that have breached their SLA (or use a **ratio**: breached / total). Only then is
the colour representative.

---

## 3. Accessibility (non-negotiable)

- **WCAG 2.1 Success Criterion 1.4.1 — Use of Color:** colour must not be the
  only visual means of conveying information. Red–green is also the most common
  colour-vision deficiency pairing.
- **Always pair colour with text and/or an icon.** The **HPE Design System** rule
  for status indicators: at least **3 of 4** elements present —
  **colour + icon + shape + text**.
- Status indicators should be semantic (success / warning / critical / unknown),
  not decorative.

Our implementation addresses this with a **visible status word**
(`Clear` / `Monitor` / `Action needed`) next to the coloured dot, with the dot
marked decorative (`aria-hidden`).

---

## 4. How it maps to our dashboard

| KPI | Nature | Recommended model |
|---|---|---|
| Active Clamps | Work queue (holding a vehicle) | SLA/age-based (tight SLA) |
| Pending Appeals | Work queue | SLA/age-based (`0–3`/`4–7`/`>7` days) |
| Unpaid Citations | Balance / receivable (a *stock*, not a queue) | Ratio/aging, not strict SLA |

Suggested SLA-based follow-up (requires a controller change to expose counts):

```php
// Citations — reuse the existing Overdue status (due_date < now(), 30-day SLA
// via config('itevcms.citation_overdue_days')).
$citationBreaches = Citation::where('status', CitationStatus::Overdue)->count();

// Appeals — age vs SLA (ServiceNow-style 0–3 / 4–7 / >7 day buckets).
$appealBreaches = Appeal::whereIn('status', [AppealStatus::Submitted, AppealStatus::UnderReview])
    ->where('submitted_at', '<', now()->subDays(7))
    ->count();

// Clamps — age vs SLA (tight, holding a vehicle).
$clampBreaches = ClampingRecord::where('status', ClampingStatus::AwaitingPayment)
    ->where('clamped_at', '<', now()->subHours(72))
    ->count();
```

Then colour each card by `breachCount` (`0` = green, `1..N` = amber, `> N` = red),
or by a **ratio** (`breaches / total`) for receivables.

### Caveats

- **Unpaid Citations** is a receivable balance. If legacy debt stays open for
  months a strict SLA pins it **red permanently** → alarm fatigue. Prefer a
  ratio (e.g. green < 10%, amber 10–25%, red > 25%) or a generous SLA.
- Age-based colouring needs **controller/query changes**; our current
  implementation is deliberately **view-only**.
- Thresholds should be revisited against real operational baselines after launch.

---

## 5. Sources

- W3C — Understanding WCAG 2.1, SC 1.4.1 "Use of Color":
  https://www.w3.org/WAI/WCAG21/Understanding/use-of-color.html
- HPE Design System — Status indicator (3-of-4 cues):
  https://design-system.hpe.design/templates/status-indicator
- Jira Service Management — SLA display formats and time frames (green/amber/red):
  https://confluence.atlassian.com/servicemanagementserver101/sla-display-formats-and-time-frames-1455887549.html
- Tungsten Automation TotalAgility — Service level agreement (work-queue SLA thresholds):
  https://docshield.tungstenautomation.com/KTA/en_US/7.8.0-dpm5ap0jk8/help/TA/All_Shared/Workflow/c_actpropsla.html
- ServiceNow Community — Best practices to manage aging tickets (age buckets):
  https://www.servicenow.com/community/developer-forum/please-suggest-some-best-practices-to-manage-ageing-tickets-and/m-p/3115056
- IBM — Monitoring status through KPIs (green/yellow/red/grey):
  https://www.ibm.com/docs/en/ioc/1.6.0?topic=data-monitoring-status-through-kpis
- Carbon Design System — Status indicators (highest-attention colour):
  https://preview.carbondesignsystem.com/building-blocks/core/patterns/status-indicators
- ClearPoint Strategy — RAG statuses for KPIs (thresholds/tolerance):
  https://www.clearpointstrategy.com/blog/establish-rag-statuses-for-kpis
