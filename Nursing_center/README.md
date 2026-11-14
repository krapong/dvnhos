# Nursing Command Center FY2570 — UI/UX Production Starter

## IPD module

IPD male/female wards are now supported at `/ipd` with shift reports, patient types 1–4, ward-scoped accounts, central MySQL storage and revision history. See [IPD-GUIDE.md](IPD-GUIDE.md) for accounts, counting conventions and the additive `php install-ipd.php` upgrade. The older prototype scope below predates this module.

## ## MySQL edition

Accounts, roles, shared daily reports and report edit history now use MySQL through PDO. Existing SQLite account IDs and password hashes have been migrated locally. See [DEPLOY-MYSQL.md](DEPLOY-MYSQL.md) for internal-server installation, migration and backups. Passwords require at least 8 characters. Approval workflow remains a prototype.

The following original starter documentation describes the earlier prototype and its historical limitations; use DEPLOY-MYSQL.md for current database and login setup.

โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ 60 พรรษา**

A Thai-language, static web UI prototype matching the **layout and overall design language** of the generated mockup: navy-blue sidebar/banner, bright KPI tiles, 12-month fiscal-year trend charts, donut comparisons, clinical incidents, and tabular reports.

> IMPORTANT: **Not production-ready.** The numbers (including comparison figures), labels, goals and clinical incident counts are **mock/sample data**, not hospital records. The badge in the UI explicitly marks this. The app has **no backend, no authentication, no HOSxP connection**, and does not claim compliance with clinical data standards. Do NOT enter real patient/person-identifiable data.

## Start

Data entry is manual. Staff enter department reports by date and shift. The planned dashboard and exports aggregate those reports after review and approval. HOSxP integration is outside the system scope.

1. Extract the ZIP.
2. Open `index.html` in Chrome or Edge (no install/build needed).
3. Navigate the sidebar to OPD, LR, HT, DM, CKD, Warfarin, Asthma/COPD and shared screens.
4. For consistent file access, optionally run `python -m http.server 8080` from the folder and visit `http://localhost:8080`.

## Pages

| File | Screen |
|---|---|
| index.html | Executive overview, excluding IPD |
| opd.html | OPD |
| lr.html | Labor room |
| ht.html | HT Clinic |
| dm.html | DM Clinic |
| ckd.html | CKD Clinic |
| warfarin.html | Warfarin Clinic |
| asthma.html | Asthma / COPD Clinic |
| entry.html | Daily data entry and locally saved demo draft |
| risks.html | Incident and risk tracking mockup |
| workforce.html | Staff/workload/productivity mockup |
| reports.html | Preview and export *demo* CSV; print to PDF via browser |
| approvals.html | Approvals screen, disabled until backend |
| settings.html | Departments and role/permission design |

## Functionality (implemented in this zip)

- Responsive sidebar and navigation links.
- Fiscal-year 2570 period selection: whole year, quarterly, monthly. Sample/demo KPIs are adjusted for chosen period, not real calculations.
- 7 configurable department indicator definitions in `config.js`.
- Dynamic HTML forms for each department; saves/loads draft **only in browser localStorage**, keyed by department, date and shift.
- Export a demo JSON draft and demo CSV report with Thai UTF-8 BOM.
- Print report / save PDF using browser print dialog.
- Risk, workforce, report and approval visual layouts.
- `docs/UI_REFERENCE.png`: a generated *visual reference*, not an exact screenshot of this HTML. The graphical reproduction is approximate (different render engines/fonts and illustration details).

## Before production — required work

1. Move frontend into React/Vite or the preferred Laravel/Inertia frontend, reuse UI CSS/components.
2. Implement Laravel REST API + MariaDB migrations and transactional input/approval workflows.
3. Real secure login (OIDC/SSO), role-based access, department scopes, CSRF, rate limits, TLS, server-side validation, audit trails, daily backups and restores.
4. Replace all `DEPARTMENT_DATA` in `app.js` with API responses aggregated from staff-entered reports. Staff manually enter department indicators by date and shift, then submit reports for review and approval. Validate duplicate reports, indicator values, clinic reporting schedules and fiscal-period aggregation.
5. Confirm indicator definitions and units, `/`-separated multi-measures from old forms, reference dates and fiscal-year boundary (1 Oct 2569 – 30 Sep 2570).
6. Confirm per-department productivity formula/weights and staffing hours before displaying a target or success status.
7. Define risk severity, case-to-event relations and whether an incident is counted in cases or event occurrences.
8. Test authorization, data isolation, mobile rendering, backend integration, PDF/Excel fidelity, SQL performance, disaster recovery and accessibility.

### Data model recommendation

`departments`, `users`, `roles`, `permissions`, `fiscal_years`, `shifts`, `indicator_definitions`, `department_indicators`, `daily_reports`, `report_values`, `staffing_records`, `incidents`, `approval_logs`, `audit_logs`.

### Integration note

IPD is expressly **out of scope**; it already exists separately. A future executive overview may link to the existing IPD system, but must not double-count encounters.

### Visual fidelity

This is a runnable **visual implementation**, not a pixel-exact reverse-engineering of a raster AI-generated mockup. For strict pixel matching, use side-by-side browser screenshots and refine CSS, font choices, branding assets and spacing with actual authorized hospital graphic assets. The emblem currently shown is a generic healthcare symbol, not an official hospital mark.
