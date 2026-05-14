# NIS2/GDPR Document Management WBS

This WBS defines the product path for turning the document registry into an audit-ready NIS2-first compliance workspace. It is intentionally conservative: system templates remain separate from tenant data, tenant copies are editable, and each implementation step must preserve existing Snipe-IT workflows.

Current direction and implementation status are tracked in [Project Direction And Status](project-direction-status.md).

Daily framework pack operations are tracked in [Compliance Framework Pack Operations WBS](compliance-framework-pack-operations-wbs.md).

Implementation status as of 2026-05-14: this WBS is functionally complete. Remaining notes in this document are verification, security or legal-review constraints rather than unfinished NIS2/GDPR document-management deliverables.

## 1. Scope And Principles

### 1.1 Product Goal

Build a document-centered compliance module that helps consultants and tenants govern NIS2/GDPR evidence, supplier relevance, asset inventory links, review cycles and delegated work without pretending to replace expert judgment.

### 1.2 Non-Negotiable Constraints

- Keep tenant isolation and template visibility intact.
- Never overwrite tenant-edited frameworks during bootstrap unless explicitly requested.
- Keep system bootstrap content hidden/read-only and store operational starter content as editable tenant records.
- Keep every UI label and coded option translatable for supported European languages plus general English.
- Prefer structured fields for audit-critical facts, leaving notes for rationale and context.
- Keep existing document, asset, supplier, ticket and user workflows working.

## 2. Regulatory And Evidence Model

### 2.1 Official Reference Sources

- ACN NIS information update and portal guidance.
- Directive (EU) 2022/2555, especially cybersecurity risk management and supply-chain security.
- D.lgs. 138/2024 and later ACN determinations/FAQ where applicable.
- Regulation (EU) 2016/679 for GDPR evidence.
- Regulation (EC) 2195/2002 for CPV vocabulary references.

### 2.2 Framework Packs

- Maintain NIS2 and GDPR starter packs as system templates.
- Bootstrap tenant-local framework copies based on tenant default language.
- Keep pack keys and source template links for traceability.
- Mark generated tenant frameworks as normal editable operational data.
- Add future packs only after schema and i18n coverage are ready.

### 2.3 Requirement Model

- Track requirement code, domain, obligation type, evidence type, delegation level, risk level, review cadence and official reference.
- Keep coverage derived from linked documents, not manually typed.
- Support primary and supporting evidence roles.
- Preserve room for later evidence-quality and remediation fields.

## 3. Document Registry

### 3.1 Core Documents

- Keep document metadata: owner, type, framework, number, version, status, classification, scope, review dates and control URL.
- Ensure framework requirements can be selected only from operational frameworks.
- Display coverage roles with translated labels.

### 3.2 Evidence Mapping

- Link documents to requirements as primary or supporting evidence.
- Keep pivot notes and covered date available for future UI enhancement.
- Expose mapping notes and covered date in the document UI.

### 3.3 Assignments

- Keep document assignments to users, assets and locations.
- Assignment API/bootstrap-table coverage includes filters, export and delegated evidence queues.
- Assignment evidence status, issuer/reviewer metadata, approval states, reminders and delegated task follow-up are implemented on the shared document assignment workflow.

## 4. Supplier NIS2 Register

### 4.1 Supplier Classification

- Track whether a supplier is NIS2 relevant.
- Track structured relevance criterion:
  - not assessed
  - ICT supply
  - non-fungible supply
  - ICT and non-fungible supply
  - not relevant
- Track criticality, assessment status, CPV codes, relevance rationale, last assessment and next review.

### 4.2 CPV Handling

- Preserve free-form CPV input for compatibility.
- Add structured relevance criterion before CPV/rationale to reduce ambiguous supplier records.
- Reusable CPV catalog/search and validation against the official code pattern are implemented.

### 4.3 Supplier Evidence

- Link supplier records to documents through document assignments and requirement mappings.
- Supplier-specific evidence dashboard shows questionnaires, contracts, SLAs, attestations and improvement plans from assigned documents and their review status.

## 5. NIS2 Inventory Link

### 5.1 Categories

- Track whether an asset category belongs in the NIS2 inventory.
- Track inventory scope: network, server, endpoint, cloud, security, identity, backup, facility, other.

### 5.2 Assets

- Track asset NIS2 relevance, scope, service impact and notes.
- Keep standard asset lifecycle unchanged.
- Tenant compliance dashboard includes NIS2 asset counts and high-impact asset drill-downs while keeping the standard asset lifecycle unchanged.

## 6. UI/UX

### 6.1 Consistency Rules

- Use existing Bootstrap/AdminLTE/Snipe-IT patterns.
- Keep table actions, toolbar layout and buttons aligned with existing document/ticket UI.
- Avoid landing-page style screens; compliance views must be operational and scan-friendly.

### 6.2 Immediate UI Coverage

- Supplier form shows NIS2 relevance, criticality, structured relevance criterion, assessment status, CPV, rationale and review dates.
- Supplier index exposes NIS2 relevance, criticality, relevance criterion, status and CPV as table columns.
- Supplier detail shows the same structured NIS2 fields.
- Document tables show linked framework requirements as compact labels with coverage role in the tooltip.
- Document index exposes framework and requirement filters backed by the existing documents API.
- Framework requirements have a work queue with framework and coverage filters backed by the existing requirements API.
- Tenant detail includes a read-only compliance dashboard for framework coverage, document reviews, NIS2 suppliers, NIS2 assets and open tickets.
- Tenant compliance dashboard counters drill down to existing tenant-filtered requirement, document, supplier, asset, framework and ticket work queues.
- Supplier qualification includes CPV catalog search and structured NIS2 assessment method, outcome and scope fields while preserving the free-form CPV field.
- Supplier detail includes a NIS2 evidence checklist and assigned-document review table for supplier evidence.
- Supplier index includes an ACN-oriented ODS export for NIS2 supplier data preparation, using the official spreadsheet template kept in `docs/ACN_Template_fornitori.ods`.
- Document section includes a delegated evidence request queue for open user/supplier evidence assignments.
- Document assignments include approval/review states and append-only assignment audit events.
- Delegated document evidence assignments send tenant reminder/escalation digests while leaving approval sign-off to reviewers.
- Customer register mirrors supplier governance where it is useful, but remains focused on client-side contracts, service-chain duties, security contacts and linked evidence.
- Customer contracts include subscriptions, service costs, renewal dates, linked signed documents and an append-only hash chain for audit traceability.
- Contract forecast report shows expected revenue, costs and net margin by month, quarter, year and contract.

### 6.3 Completion Status

- Tenant detail includes the compliance dashboard with framework, requirement, document, supplier, asset and ticket drill-down counters.
- Frameworks include a dedicated requirement matrix view covering requirements, coverage, owner, risk, review state and linked evidence.
- Document index includes framework, requirement, review status, ownership, type, tenant and company filters where relevant.
- A global requirement work queue exists for operational framework requirement filtering and export.

## 7. i18n And Locale Governance

### 7.1 Supported Locales

Only supported European locales plus general English are allowed in code-level UI. Current supported locales are the directories under `resources/lang`.

### 7.2 Translation Requirements

- Every new coded label must be present in all supported language files.
- English fallback is acceptable for existing incomplete translations, but no raw translation keys should leak.
- Starter framework content may be language-pack data, but UI labels and coded options must use translation files.

### 7.3 Known Debt

- Some non-English locale files currently contain English fallback text.
- Default permission group names remain hardcoded in English.
- Compliance packs now include tenant bootstrap keys for every supported locale. Italian keeps the ACN-oriented pack; other non-English locales use the EU baseline pack text until expert local translations are approved.

## 8. Verification Plan

### 8.1 Required Checks

- `php -l` for changed PHP files.
- `php artisan migrate --pretend --force --path=<new migration> --no-ansi`.
- `php artisan migrate --force --no-ansi` only after reviewing the migration.
- `php artisan view:cache --no-ansi && php artisan view:clear --no-ansi`.
- `php artisan snipeit:install-compliance-frameworks all --dry-run --no-ansi`.
- i18n parity check across supported locales.

### 8.2 Current Test Blocker

The local PHP test runner is available, but full test execution still requires a safe `.env.testing`. Until that exists, verification relies on lint, migration dry-runs, view cache, targeted artisan checks, API-level checks and targeted manual UI checks.

### 8.3 Current Security Snapshot

- Composer advisories were reduced from 9 to 1 during the 2026-05-10 audit.
- The remaining advisory is `firebase/php-jwt` CVE-2025-45769, low severity, blocked by the current `laravel/passport` v12 dependency constraint.
- Moving to `firebase/php-jwt` v7 requires a planned Passport/OAuth major upgrade, not an opportunistic patch.
- `npm` is not available in this environment, so frontend dependency audit and asset compilation remain unverifiable locally.

## 9. Implementation Roadmap

### Phase 1 - Safe Foundation

- Fix missing i18n keys for document coverage roles. Completed.
- Add structured NIS supplier relevance criterion. Completed.
- Keep supplier UI/API/presenter aligned. Completed.
- Document the WBS and verification criteria. Completed.

### Phase 2 - Evidence Quality

- Add evidence mapping UI fields for pivot notes and coverage date. Completed.
- Add document requirement coverage column/filter in document tables. Completed.
- Add requirement work queue. Completed.

### Phase 3 - Tenant Compliance Dashboard

- Add tenant compliance summary: framework coverage, overdue reviews, supplier reviews, NIS asset coverage and open tickets. Completed.
- Keep dashboard read-only first, then add drill-down actions. Completed.

### Phase 4 - CPV And Supplier Assessment

- Add CPV catalog/search and structured supplier assessment fields. Completed.
- Add supplier evidence checklist and review workflow. Completed.
- Add export for ACN-oriented supplier data preparation. Completed with the ACN ODS template export.

### Phase 5 - Delegation And Approvals

- Add delegated evidence requests tied to users/suppliers. Completed as an operational queue over existing document assignments.
- Add approval/review states and immutable audit events. Completed with assignment approval status fields and append-only audit event records.
- Add reminders and escalation while preserving expert sign-off. Completed with tenant daily digests for delegated evidence assignments due soon or overdue, without automatic approval changes.

### Phase 6 - Advanced Compliance Packs

- Expand official framework packs by jurisdiction/language. Completed with Italian ACN packs plus EU baseline NIS2/GDPR pack keys for every supported tenant locale, preserving existing pack keys.
- Add pack versioning and tenant diff/merge tooling. Completed with `source_pack_version`, system/tenant diff output and non-destructive tenant merge for missing framework copies or missing requirements only.
- Add daily superadmin pack operations console. Completed with pack status, tenant diff rows, explicit non-destructive apply actions and immutable audit events.
- Add import/export for Word/Excel-based consultant frameworks. Completed with non-destructive tenant import for CSV/Excel/Word table files and framework export to CSV/XLSX/DOCX.

### Phase 7 - Client Contracts And Service Chain

- Add customer register in settings alongside suppliers, with tenant/company ownership, contacts, NIS2 profile, service role, criticality, review dates and linked document evidence. Completed.
- Add customer contracts with status, owner, signed document, renewal/notice dates, subscriptions, supplier-backed service costs and monthly revenue/cost/net indicators. Completed.
- Add immutable customer-contract event log using SHA-256 payload hashes and previous-hash chaining for create/update/delete snapshots. Completed.
- Add contract revenue forecast report with monthly, quarterly, yearly and per-contract deliverables. Completed.
- Keep customer document assignments available through the same document evidence workflow used by users, assets, locations and suppliers. Completed.
