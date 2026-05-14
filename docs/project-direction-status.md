# Project Direction And Status

Last updated: 2026-05-14

## Product Direction

CodyCloud Asset is moving from a Snipe-IT fork into a multitenant compliance operations platform for European customers. The product direction remains NIS2-first, with GDPR support where evidence, accountability and supplier/customer governance overlap, and AI Act support as an EU-baseline evidence and classification pack.

The target is not to replace expert consultants. The platform should remove repetitive work, keep records structured, expose gaps early and preserve audit-grade evidence for decisions made by qualified users.

## Operating Principles

- Tenant data stays tenant-scoped by default.
- System bootstrap content is shared only as hidden/read-only starter material.
- Tenant operational copies are editable and must not be overwritten by bootstrap updates.
- Tenant sharing is explicit and limited to controlled companies where the tenant chooses to use the company hierarchy.
- Code-level UI supports only European locales plus general English.
- Audit-critical data uses structured fields, not only free-text notes.
- Immutability-sensitive records keep append-only events, payload checksums and hash chaining where legal or evidentiary value matters.
- Framework pack sync operations create append-only audit events with SHA-256 pack checksums and per-pack hash chaining.

## Current Functional Surface

### Multitenancy

- Tenant/company scoping is a central guardrail for new features.
- Tenant settings include branding and default locale direction for bootstrap operations.
- System framework templates are separated from tenant operational frameworks.
- Compliance pack synchronization is non-destructive: missing records can be created, but tenant-edited fields are not overwritten automatically.

### Document And Compliance Frameworks

- Document frameworks and requirements provide the shared model for NIS2/GDPR evidence.
- Framework packs exist for all supported locales; Italian is ACN-oriented, while other locales currently use the EU baseline until expert translations are validated.
- AI Act and NIS2 pack sources are formalized in `docs/compliance-source-register.md`; NIS2 pack coverage is audited in `docs/nis2-pack-audit.md`; selected-tenant rollout is controlled by `docs/compliance-pack-rollout-ai-act-nis2.md`.
- Requirements can be linked to document evidence with primary/supporting roles.
- Requirement queues, filters and document assignment flows are being kept aligned with Snipe-IT table patterns.
- Framework requirement matrix views show requirement coverage, owner, risk/review state and linked evidence in one audit-oriented view.
- Word/Excel/CSV consultant framework import and export are supported as operational tenant data, not as global system pollution.
- Daily pack operations are managed through a superadmin console that shows pack checksums, system-template status, tenant copy diffs and immutable sync events.
- AI Act/NIS2 pack expansion is complete as of 2026-05-14 at pack/source/rollout-documentation level. `nis2_it` remains the only national overlay; non-Italian NIS2 jurisdictions remain `review_required`.
- Compliance pack operations are complete as of 2026-05-14: tenant managers can run safe tenant-scoped pack updates from tenant settings, and superadmins can apply a pack to explicitly selected compatible tenants without overwriting tenant edits.

### NIS2 Inventory And Risk

- Suppliers, categories and assets carry NIS2 metadata such as relevance, criticality, CPV, assessment state, inventory scope and service impact.
- NIS2 requirements do not carry intrinsic risk. Risk is calculated from asset/category/service impact data and exposed through reports.
- CPV linkage is handled for supplier/customer-chain governance while preserving existing free-form compatibility fields.

### Customers, Contracts And Revenue Forecast

- The customer register is intended to mirror supplier governance only where useful.
- Customer records focus on client-side contracts, service-chain responsibilities, security contacts, NIS2 profile, criticality, review dates and linked evidence.
- Customer contracts include status, owner, signed document, renewal and notice dates, subscription revenue, supplier-backed service costs and net indicators.
- Contract events use SHA-256 payload hashes and previous-hash chaining for traceability.
- Contract forecast reporting covers expected revenue, costs and net margin by month, quarter, year and contract.

## Security And Quality Snapshot

- Composer advisories were reduced from 9 to 1.
- Remaining Composer advisory: `firebase/php-jwt` CVE-2025-45769, low severity, blocked by `laravel/passport` v12 requiring `firebase/php-jwt` v6. Moving to JWT v7 requires a Passport/OAuth major upgrade and must be planned separately.
- Updated vulnerable dependencies include `onelogin/php-saml`, `robrichards/xmlseclibs`, `phpseclib/phpseclib`, `psy/psysh` and `symfony/process`.
- PHP syntax lint has passed for changed PHP files and for the main app/config/database/routes/tests PHP tree.
- Laravel package discovery and view compilation are part of the required verification path after framework or UI edits.
- `npm` is not installed in this environment, so frontend dependency auditing and asset compilation cannot be verified locally here.

## Repository Hygiene Snapshot

- Active web/import debug statements that could expose personal or import data were removed.
- A historical migration `var_dump` was removed.
- No merge conflict markers were found in app, config, database, resources, routes, tests or docs during the audit.
- Ignored local artifacts are present and intentionally not deleted by automated cleanup: `.env`, storage logs, PHPUnit cache and the root backup zip. These must remain outside version control and should be moved outside the deployment root before production hardening if they contain real data.
- The working tree is intentionally large and dirty because several feature tracks are in progress; unrelated changes must not be reverted during cleanup.

## Verification Expectations

Before merging or deploying this branch, run:

- `composer validate --strict --no-interaction`
- `composer audit --no-interaction`
- `php -l` on changed PHP files
- `php artisan package:discover --no-ansi`
- `php artisan view:cache --no-ansi && php artisan view:clear --no-ansi`
- `git diff --check`
- Targeted feature tests once a valid `.env.testing` is available

## Known Gaps

- Complete automated test execution is blocked locally until a safe `.env.testing` is provided.
- The remaining JWT advisory requires a planned Passport major upgrade.
- Frontend dependency audit is blocked until Node/npm are available.
- Expert legal review is still required before treating framework pack content as jurisdiction-specific advice outside the Italian ACN-oriented starter pack. ACN operational material and AI Act timeline updates must be rechecked before client-specific advice or filing.
