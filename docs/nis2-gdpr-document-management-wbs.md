# NIS2/GDPR Document Management WBS

This WBS defines the product path for turning the document registry into an audit-ready NIS2-first compliance workspace. It is intentionally conservative: system templates remain separate from tenant data, tenant copies are editable, and each implementation step must preserve existing Snipe-IT workflows.

## 1. Scope And Principles

### 1.1 Product Goal

Build a document-centered compliance module that helps consultants and tenants govern NIS2/GDPR evidence, supplier relevance, asset inventory links, review cycles and delegated work without pretending to replace expert judgment.

### 1.2 Non-Negotiable Constraints

- Keep tenant isolation and template visibility intact.
- Never overwrite tenant-edited frameworks during bootstrap unless explicitly requested.
- Store official/starter content as editable tenant records.
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
- Next implementation step: add API/bootstrap-table for assignments with filters and export.
- Later: add assignment evidence status, issuer review and delegated task follow-up.

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
- Future step: add reusable CPV catalog/search and validation against official code pattern.

### 4.3 Supplier Evidence

- Link supplier records to documents through document assignments and requirement mappings.
- Future step: add supplier-specific evidence dashboard: questionnaires, contracts, SLAs, attestations and improvement plans.

## 5. NIS2 Inventory Link

### 5.1 Categories

- Track whether an asset category belongs in the NIS2 inventory.
- Track inventory scope: network, server, endpoint, cloud, security, identity, backup, facility, other.

### 5.2 Assets

- Track asset NIS2 relevance, scope, service impact and notes.
- Keep standard asset lifecycle unchanged.
- Future step: add tenant dashboard for NIS2-relevant assets by scope, impact and missing owner/document evidence.

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

### 6.3 Future UI Work

- Add compliance dashboard to tenant detail.
- Add framework requirement matrix view: requirements, coverage, owner, risk, due review and linked evidence.
- Add document filters for framework, requirement, review status and assignment target.
- Add global requirement index for operational work queues.

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
- Compliance packs currently exist only for `it-IT` and `en-US`; other tenant locales bootstrap from English.

## 8. Verification Plan

### 8.1 Required Checks

- `php -l` for changed PHP files.
- `php artisan migrate --pretend --force --path=<new migration> --no-ansi`.
- `php artisan migrate --force --no-ansi` only after reviewing the migration.
- `php artisan view:cache --no-ansi && php artisan view:clear --no-ansi`.
- `php artisan snipeit:install-compliance-frameworks all --dry-run --no-ansi`.
- i18n parity check across supported locales.

### 8.2 Current Test Blocker

The local PHP test runner is currently incomplete: `php artisan test` fails because `SebastianBergmann\Environment\Console` is missing and `vendor/bin/phpunit` is absent. Until dependencies are repaired, verification relies on lint, migration, view cache, tinker/API-level checks and targeted manual UI checks.

## 9. Implementation Roadmap

### Phase 1 - Safe Foundation

- Fix missing i18n keys for document coverage roles.
- Add structured NIS supplier relevance criterion.
- Keep supplier UI/API/presenter aligned.
- Document the WBS and verification criteria.

### Phase 2 - Evidence Quality

- Add evidence mapping UI fields for pivot notes and coverage date.
- Add document requirement coverage column/filter in document tables.
- Add requirement work queue.

### Phase 3 - Tenant Compliance Dashboard

- Add tenant compliance summary: framework coverage, overdue reviews, supplier reviews, NIS asset coverage and open tickets.
- Keep dashboard read-only first, then add drill-down actions.

### Phase 4 - CPV And Supplier Assessment

- Add CPV catalog/search and structured supplier assessment fields.
- Add supplier evidence checklist and review workflow.
- Add export for ACN-oriented supplier data preparation.

### Phase 5 - Delegation And Approvals

- Add delegated evidence requests tied to users/suppliers.
- Add approval/review states and immutable audit events.
- Add reminders and escalation while preserving expert sign-off.

### Phase 6 - Advanced Compliance Packs

- Expand official framework packs by jurisdiction/language.
- Add pack versioning and tenant diff/merge tooling.
- Add import/export for Word/Excel-based consultant frameworks.
