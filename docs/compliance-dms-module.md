# Modulo Compliance / DMS — Riferimento tecnico e stato

> Documentazione dei moduli **custom** (non standard Snipe-IT) per la gestione documentale e la
> mappatura evidenze ↔ requisiti sui framework di compliance (GDPR, NIS2, ISO, D.Lgs 81, AI Act).
> Stato dati verificato in sola-lettura sul DB di produzione `codyclou1_asset` il **2026-08-12**.
> Citazioni `file:riga` relative alla root del repo.

## Indice
1. [Stato reale (verificato)](#1-stato-reale-verificato)
2. [Gap e come colmarli](#2-gap-e-come-colmarli)
3. [Data model & relazioni](#data-model--relationships)
4. [CRUD, associazione evidenze & superficie API](#crud--association-flow--api-surface--compliancedms-modules)
5. [Pack di compliance — catalogo e come applicarli](#compliance-packs--available-catalogs--how-to-apply-one-to-a-company)

---

## 1. Stato reale (verificato)

Il modulo **non è vuoto**: contiene lavoro NIS2 reale e completo. (Un censimento via API REST standard lo faceva sembrare vuoto perché i moduli custom non sono esposti dagli endpoint standard e le liste sono filtrate per company — vedi §4.)

**Framework di sistema (template globali, `company_id NULL`, `is_system_template=1`) — 7, catalogo pronto all'uso:**

| id | Template | pack_key | # requisiti |
|---|---|---|---|
| 109 | NIS2 IT – Allegato 1 | `nis2_it_allegato_1` | **87** |
| 110 | NIS2 IT – Allegato 2 | `nis2_it_allegato_2` | **116** |
| 107 | ISO/IEC 27001:2022 | `iso27001_it` | 68 |
| 108 | ISO 9001:2015 | `iso9001_it` | 35 |
| 106 | D.Lgs. 81/2008 | `dl81_it` | 25 |
| 112 | AI Act UE | `ai_act_it` | 16 |
| 111 | GDPR – Evidenze documentali | `gdpr_eu` | 6 |

**Framework operativi (per-company) e copertura evidenze:**

| Company | Framework operativo | Requisiti | Con evidenza | Copertura |
|---|---|---|---|---|
| **CodyCloud** (#1) | NIS2 IT – Allegato 2 (fw #4) | 116 | 116 | **100%** |
| CodyCloud (#1) | D.Lgs. 81/2008 (fw #2) | 0 | 0 | *stub vuoto* |
| **Italway Srl** (#100) | NIS2 IT – Allegato 1 (fw #104) | 87 | 87 | **100%** |
| Suez Italy, Ecosistem, Logica 2.0, iblue, Econet, Deca | — nessun framework istanziato — | — | — | — |

**Documenti:** 42 totali (25 `active`, 17 `draft`). CodyCloud 21, Italway 14, gli altri clienti 1-2 ciascuno.
**Evidenze:** **502** mappature requisito↔documento, coperte da **27** documenti distinti (una policy copre più controlli — normale). I documenti CodyCloud sono evidenze NIS2 reali (Valutazione del rischio, Piano gestione incidenti, Piano di continuità operativa, Dichiarazione ACN `DNISA00043174`, Riepilogo Categorizzazione ACN 06/2026, ecc.).

> ⚠️ **Anomalia dato da tenere presente:** il framework operativo NIS2 Allegato 2 di CodyCloud (fw **#4**) è **hand-built** (slug `nis2`, `source_pack_key=NULL`) — è la fonte da cui è stato *estratto* il pack `nis2_it_allegato_2`. Non essendo pack-linked, gli strumenti di sync/pack **non lo riconoscono**: ri-applicare il pack Allegato 2 a CodyCloud **creerebbe un duplicato**, non un aggiornamento di #4. Dettagli e workaround in §5.2.4 / §5.4.

## 2. Gap e come colmarli

**Cosa manca (per-nome):**
- **CodyCloud**: ha Allegato 2 ✓; mancano **Allegato 1**, **GDPR**, ISO 27001, ISO 9001, AI Act; D.Lgs 81 è uno stub da popolare.
- **Italway**: ha Allegato 1 ✓; mancano Allegato 2, GDPR, ecc.
- **6 clienti minori** (tenant Suez, EU): nessun pack ancora applicato.

**Come colmare (ricette operative — dettaglio con comandi e avvertenze in §5.4):**
- **GDPR → CodyCloud**: dashboard super-admin → pack `gdpr_eu` → *Apply to tenant* CodyCloud (audita l'evento). Anteprima: `php artisan snipeit:sync-compliance-framework-packs gdpr_eu --tenant_id=1`.
- **NIS2 Allegato 1 → CodyCloud**: bloccato dal gate giurisdizione (CodyCloud=EU). Due opzioni sicure in §5.4.3 (cambiare la giurisdizione del tenant a IT e usare la dashboard, **oppure** `snipeit:install-compliance-frameworks nis2_it_allegato_1 --company_id=1 --visibility=private`). **Mai** usare "apply all" mentre la giurisdizione è IT (creerebbe il duplicato Allegato 2 dell'anomalia sopra).
- **Pack sui clienti sub-company** (Econet/iblue/Logica/Deca/Ecosistem/Suez Italy): il percorso tenant/dashboard installa **solo sulla company root del tenant**; per una sub-company specifica usare `snipeit:install-compliance-frameworks <pack_key> --company_id=<id> --visibility=private` (dettaglio §5.4.4).

> **Regola d'oro:** sempre `--dry-run` prima; mai `--update-existing` se non si intende sovrascrivere; preferire i percorsi dashboard/`sync` (che scrivono l'audit a prova di manomissione) al comando `install` grezzo (che **non** registra l'evento pack).

---

---

# Data model & relationships

All compliance/DMS models extend `App\Models\SnipeModel` and use `Watson\Validating\ValidatingTrait` (validation rules live in each model's `$rules`). Soft deletes are used throughout. Two different multi-tenant scoping traits are applied depending on the entity — this distinction drives everything else, so it is documented first.

## 0. Two scoping regimes (read this first)

| Trait | Applied to | Global scope behaviour |
|---|---|---|
| `TenantTemplateTrait` | `DocumentFramework`, `DocumentType` | "Template visibility": a row is visible if `company_id IS NULL` (global), **or** `company_id ∈ current company context`, **or** it belongs to an **ancestor** company **and** `visibility_type = 'descendants'`. |
| `CompanyableTrait` | `Document`, `DocumentAssignment` | Standard Snipe-IT per-company scope (`CompanyableScope`). |
| *(neither)* | `TenantService` | Manually scoped by `tenant_id` (+ optional `company_id`) via `TenantRecordGuard`; no global scope. |

- `TenantTemplateTrait` — `app/Models/Traits/TenantTemplateTrait.php`. Constants `VISIBILITY_PRIVATE/DESCENDANTS/GLOBAL` (`:10-12`). `bootTenantTemplateTrait()` registers the `tenant_template_visibility` global scope delegating to `Company::scopeTemplateVisibility` (`:14-19`). Helpers: `isGlobalTemplate()` = `company_id IS NULL` (`:31-34`), `isSharedToDescendants()` (`:36-39`), `getVisibilityLabelAttribute()` (`:50-53`).
- The actual visibility SQL is `Company::scopeTemplateVisibility()` — `app/Models/Company.php:878-907`. Users who can use the global tenant context bypass it (`:884-886`); otherwise it builds the `NULL company OR current-company OR (ancestor-company AND visibility_type='descendants')` predicate (`:892-906`).
- `CompanyableTrait` — `app/Models/Traits/CompanyableTrait.php:18-20` registers `CompanyableScope`.

---

## 1. `DocumentFramework`

**File:** `app/Models/DocumentFramework.php` · **Table:** `document_frameworks` (`:24`) · **Traits:** SoftDeletes + `TenantTemplateTrait` (`:20-21`).

A **framework** is a compliance/regulatory reference (NIS2 Allegato 1/2, ISO 27001, GDPR, D.Lgs 81, AI Act…). It exists in two forms: a **system template** (global master, `company_id NULL`) and an **operational** per-company instance (§3).

**Key columns** (`$fillable` `:58-86`, `$casts` `:88-100`):

| Column | Meaning / notes |
|---|---|
| `name`, `slug` | Slug auto-derived from name if blank; both setters slugify (`setNameAttribute :332-339`, `setSlugAttribute :326-330`). `slug` has a DB `unique` index (migration `2026_04_17_170000:19`). |
| `framework_type` | Enum `law/regulation/standard/policy/internal/custom` (`getFrameworkTypeOptions :130-140`; rule `max:40` `:34`). |
| `compliance_domain` | Soft FK (string key) into `compliance_domains.key` — e.g. `nis2`, `gdpr`, `iso27001` (see `complianceDomain()` relation below). |
| `jurisdiction`, `version`, `authority_name`, `framework_code`, `external_reference_url`, `compliance_objective`, `description` | Descriptive metadata. Empty-string setters coerce to `null` (`:341-364`). |
| `status` | Enum `draft/active/superseded/archived` (rule `:42`; `getStatusOptions :170-178`). |
| `effective_from` / `effective_to` | Validity window (`date` casts) — the active period of the framework. |
| `review_cadence_months`, `last_reviewed_at` | Periodic-review cadence. `review_due_at` accessor = `(last_reviewed_at ?? created_at) + cadence` (`:286-295`); `reviewDueWithin($days)` (`:311-316`). |
| `is_active`, `sort_order` | List/ordering flags. `scopeActive :260-263`, `scopeOrdered :265-268`. |
| `company_id` | `NULL` ⇒ global template; set ⇒ owned by that company. Cast `integer`. |
| `visibility_type` | Enum `private/descendants/global` (rule `:48`). |
| `is_system_template` | `boolean`. `true` ⇒ global master template; `false` ⇒ operational (§3). `isSystemTemplate() :373-376`. |
| `source_framework_id` | Self-FK to the system template this instance was cloned from (`sourceFramework()` `:195-198`). |
| `source_pack_key`, `source_pack_version` | Identity + version of the config pack that seeded it (§3). |
| `locale` | Pack locale (e.g. `it-IT`, `en-US`). |
| `owner_id`, `created_by` | Owner user / creator. `created_by` is `$hidden` (`:26`). |

**Relationships**

| Method | Kind | Target · FK |
|---|---|---|
| `documents()` `:180-183` | hasMany | `Document` · `documents.document_framework_id` |
| `requirements()` `:185-188` | hasMany | `DocumentFrameworkRequirement` · `document_framework_requirements.document_framework_id` |
| `activeRequirements()` `:205-208` | hasMany (filtered) | requirements where `is_active` & not soft-deleted |
| `complianceDomain()` `:190-193` | belongsTo | `ComplianceDomain` · local `compliance_domain` → owner key `key` (string, no DB constraint) |
| `sourceFramework()` `:195-198` | belongsTo (self, `withTrashed`) | `DocumentFramework` · `source_framework_id` |
| `owner()` `:200-203` | belongsTo (`withTrashed`) | `User` · `owner_id` |
| `company()` (trait) | belongsTo | `Company` · `company_id` (`TenantTemplateTrait:21-24`) |

**Scopes:** `operational()` = `is_system_template = false AND company_id IS NOT NULL` (`:270-275`); `systemTemplates()` = `is_system_template = true` (`:277-280`); `withReviewCadence()` (`:302-308`).

**Derived:** `coverage_summary` accessor rolls up requirement coverage into `{total, covered, at_risk, supporting_only, missing, coverage_percent}` by reading each requirement's `coverage_status` (`:210-250`). `isNis2Domain()` / `looksLikeNis2Domain()` fuzzy-match the NIS2 domain from `compliance_domain`/`framework_code`/`slug`/`name` (`:147-168`, `:378-385`) — used to force `risk_level` to `not_applicable` on NIS2 requirements.

**Migrations:** created minimal in `2026_04_17_170000_create_documents_module_tables.php:16-24`; metadata columns added `2026_04_19_003000_expand…:12-46`; `compliance_domain`/`compliance_objective` `2026_05_07_100000…:11-19`; `company_id`+`visibility_type` (default `global`) `2026_04_17_200000…:40-60`; `is_system_template`/`source_framework_id`/`source_pack_key`/`locale` `2026_05_07_130000…:26-42`; `source_pack_version` `2026_05_08_113000…:12-16`; `last_reviewed_at` `2026_07_02_020000`.

---

## 2. `DocumentFrameworkRequirement`

**File:** `app/Models/DocumentFrameworkRequirement.php` · **Table:** `document_framework_requirements` (`:29`) · **Traits:** SoftDeletes.

A **requirement** is a single controllable obligation inside a framework (e.g. NIS2 Art. 24 measure). It is the unit that documents provide **evidence** against.

**Key columns** (`$fillable :35-58`, `$casts :60-71`, `$rules :73-95`):

| Column | Meaning / notes |
|---|---|
| `document_framework_id` | Owning framework (FK, cascade delete). |
| `parent_id` | Legacy single-parent hierarchy pointer (§4). |
| `code`, `title` | Requirement identifier + label (both required). |
| `domain` | Free-text sub-domain label. |
| `obligation_type` | Enum: `governance/registration/risk_management/incident_reporting/supply_chain/asset_inventory/business_continuity/training/privacy_governance/custom` (`obligationTypeOptions :246-260`). |
| `evidence_type` | Enum: `policy/procedure/register/assessment/contract/technical_report/incident_record/training_record/attestation/other` (`evidenceTypeOptions :262-276`). |
| `delegation_level` | Enum `owner_review/delegable/external_evidence/consultant_only` (rule `:86`; default `owner_review` in migration). |
| `risk_level` | Enum `not_applicable/low/medium/high/critical` (rule `:87`). **Effective** value forced to `not_applicable` for NIS2 frameworks (`getEffectiveRiskLevelAttribute :314-325`). |
| `is_mandatory`, `is_active` | Booleans (default `true`). |
| `minimum_required_documents` | **Coverage threshold** — how many *healthy primary* documents are needed to consider the requirement covered. DB default `1` (migration `2026_05_19_120000…:14`); model default `1` (`$attributes :31-33`); accessor floors at `0` (`:434-437`); setter coerces `''`/`null`→`1`, floors at `0` (`:466-469`). **`0` ⇒ auto-covered / not-applicable** (§5). |
| `default_document_type_id` | Suggested `DocumentType` for evidence. |
| `official_reference`, `source_url`, `evidence_guidance`, `applicability_notes`, `description` | Descriptive / guidance fields (empty→null setters `:456-464`). |
| `review_frequency_months`, `sort_order`, `owner_id`, `created_by` | Cadence / ordering / ownership. |

**Coverage constants** (`:22-25`): `COVERAGE_MISSING='missing'`, `COVERAGE_SUPPORTING_ONLY='supporting_only'`, `COVERAGE_AT_RISK='at_risk'`, `COVERAGE_COVERED='covered'`.

**Relationships**

| Method | Kind | Target · FK / pivot |
|---|---|---|
| `framework()` `:133-136` | belongsTo | `DocumentFramework` · `document_framework_id` |
| `parent()` `:138-141` | belongsTo (self, `withTrashed`) | via `parent_id` (legacy single parent) |
| `children()` `:150-153` | hasMany (self) | via `parent_id` |
| `parents()` `:143-148` | belongsToMany (self, `withTrashed`, timestamps) | pivot `document_framework_requirement_parents`, keys `child_requirement_id` → `parent_requirement_id` |
| `childRequirements()` `:155-159` | belongsToMany (self, reversed) | same pivot, keys swapped |
| `owner()` `:161-164` | belongsTo (`withTrashed`) | `User` · `owner_id` |
| `defaultDocumentType()` `:166-169` | belongsTo (`withTrashed`) | `DocumentType` · `default_document_type_id` |
| `documents()` `:171-176` | belongsToMany | `Document` via `document_framework_requirement_document`, pivot `coverage_role, notes, covered_at, created_by` + timestamps |
| `primaryDocuments()` `:178-181` | belongsToMany (filtered) | `documents()` where `pivot.coverage_role = 'primary'` |
| `supportingDocuments()` `:188-191` | belongsToMany (filtered) | `documents()` where `pivot.coverage_role = 'supporting'` |
| `healthyPrimaryDocuments()` `:183-186` | belongsToMany (filtered) | `primaryDocuments()->currentForCoverage()` (only "live" docs, §5) |

**Scopes:** `active :193-196`, `ordered :198-201`, `forFramework($id) :203-206`, `visibleThroughFramework :208-211` (only requirements whose framework is `operational()`).

**Migrations:** table + evidence pivot created `2026_04_19_003000…:48-95`; obligation/evidence/delegation/risk/reference columns `2026_05_07_100000…:21-45`; `minimum_required_documents` `2026_05_19_120000…:12-16`; parents pivot `2026_05_11_130000` (§4).

---

## 3. Template → operational mechanism (precise)

Frameworks/requirements are seeded from **config packs** (`config/compliance_frameworks.php` → `packs.*`) by `App\Support\Compliance\ComplianceFrameworkInstaller`. There are **two installs of the same pack**, producing two distinct rows:

**A. System template** — `installSystemPack()` (`ComplianceFrameworkInstaller.php:128-139`):
- `company_id = NULL`, `visibility_type = 'global'`, `is_system_template = true`, `source_framework_id = NULL` (`link_system_source = false`).
- `assertOwnershipOptions()` enforces: system templates **must** be global + company-less (`:298-319`).
- These are the 7 global masters (NIS2 Allegato1/2, ISO 27001, ISO 9001, D.Lgs 81, GDPR, AI Act).

**B. Operational per-company instance** — `installCompanyPack()` (`:141-160`) / `bootstrapTenant()` (`:74-126`, installs into the tenant **root company**):
- `company_id = <company>`, `visibility_type = 'private'` (or `descendants`; **never `global`** — guarded `:147-149`, `:316-318`), `is_system_template = false`, `link_system_source = true`.
- `source_framework_id` is resolved by `systemFrameworkIdForPack()` — the id of the row with matching `source_pack_key`, `is_system_template = true`, `company_id IS NULL` (`:321-328`). This is the **link back to the master template**.

**Both** installs stamp `source_pack_key = <packKey>` and `source_pack_version = packVersion(pack)` (`:179-180`), plus `locale`. The shared, immutable identity across a template and all its operational copies is **`source_pack_key`**; the FK **`source_framework_id`** additionally pins an operational row to its specific master.

**Key consequence:** an operational framework is a *full copy*, not a view. Its **requirements are copied per-instance** (each requirement's `document_framework_id` points at the operational framework, `:232-265`), and **evidence pivots attach to the operational framework's own requirement rows** — so coverage is tracked independently per company. Requirement upsert matches on `(document_framework_id, code)` (`:245-248`); parent links are re-synced by code after all requirements exist (`:267-293`).

**Identifying each kind at query time:**
- System template: `is_system_template = true` (scope `systemTemplates()`, `DocumentFramework.php:277-280`).
- Operational: `is_system_template = false AND company_id IS NOT NULL` (scope `operational()`, `:270-275`).

`DocumentType` participates in the same template regime (global types seeded company-less, `visibility_type='global'`; installer auto-creates missing global types for requirement defaults, `:357-389`).

---

## 4. Requirement hierarchy: `parent_id` vs `document_framework_requirement_parents`

There are **two coexisting** hierarchy representations; the pivot is the current one, `parent_id` is legacy but still honored.

- **Legacy:** `document_framework_requirements.parent_id` — a single self-referencing FK (`nullOnDelete`), created with the table (`2026_04_19_003000…:51`, `:70-71`). Exposed via `parent()` / `children()`.
- **Current (many-to-many):** table `document_framework_requirement_parents(child_requirement_id, parent_requirement_id)` — created `2026_05_11_130000_create_document_framework_requirement_parents.php:13-27`, `unique(child, parent)` (`:19`), both FKs `cascadeOnDelete` (`:23-26`). The same migration **backfills** the pivot from existing `parent_id` values (`:34-55`). Exposed via `parents()` / `childRequirements()`.

**Reconciliation logic** (model): `parentPivotTableExists()` caches a `Schema::hasTable` check so the code degrades gracefully when the pivot is absent (`:122-131`). The accessors **merge both sources**: `getParentRequirementIdsAttribute()` (`:334-360`) and `getParentRequirementsAttribute()` (`:362-388`) return pivot parents **plus** the legacy `parent_id` if it isn't already represented. On install, the installer writes **both**: it sets `parent_id` to the first parent code and `sync()`s the full parent set into the pivot (`ComplianceFrameworkInstaller.php:283-292`).

---

## 5. Evidence pivot semantics (`document_framework_requirement_document`)

This pivot is the **requirement ↔ document evidence mapping** — the heart of coverage.

**Schema** (`2026_04_19_003000…:80-95`): `document_framework_requirement_id`, `document_id`, `coverage_role` (`string(20)`, **default `'primary'`** `:84`), `notes`, `covered_at` (timestamp), `created_by`, timestamps. `unique(document_framework_requirement_id, document_id)` (`:94`) — a given document maps to a requirement at most once. Both FKs `cascadeOnDelete` (`:90-93`). Exposed from both sides: `DocumentFrameworkRequirement::documents()` (`:171-176`) and `Document::frameworkRequirements()` (`Document.php:164-169`), each with `->withPivot(['coverage_role','notes','covered_at','created_by'])`.

**`coverage_role` values** (only two; `Document::COVERAGE_PRIMARY='primary'` / `COVERAGE_SUPPORTING='supporting'`, `Document.php:64-66`; option labels `coverageRoleOptions() :366-372`):
- **`primary`** — the document is a load-bearing control that *satisfies* the requirement; counts toward the minimum.
- **`supporting`** — corroborating/ancillary evidence; does **not** count toward the minimum on its own.

**Coverage computation** — `getCoverageStatusAttribute()` (`DocumentFrameworkRequirement.php:398-422`), driven by `minimum_required_documents` (call it *min*) and three counts (total mapped docs, primary docs, *healthy* primary docs):

1. `min === 0` ⇒ **`covered`** (requirement is not-applicable / needs no evidence).
2. total mapped docs `=== 0` ⇒ **`missing`**.
3. primary docs `=== 0` (only supporting attached) ⇒ **`supporting_only`**.
4. healthy-primary count `< min` ⇒ **`at_risk`**.
5. otherwise ⇒ **`covered`**.

"**Healthy primary**" = `primaryDocuments()->currentForCoverage()` (`:183-186`, `:451-454`). `Document::scopeCurrentForCoverage()` (`Document.php:287-299`) requires the document to be: status `active`, within its `effective_at`/`next_review_at` window, **and** to actually have a non-deleted uploaded file (`scopeHasCoverageUpload :301-323`, verified against `action_logs` `uploaded` minus `upload deleted`). So an expired, draft, or file-less "primary" document silently drops a requirement to `at_risk`.

Shortfall helpers: `document_shortfall_count = max(0, min − healthy_primary)` (`:429-432`), `document_minimum_satisfied` (`:424-427`). The framework rollup (`coverage_summary`, §1) uses `withCount` of `documents`, `primaryDocuments`, and healthy primaries to compute these in bulk (`DocumentFramework.php:214-220`).

---

## 6. `Document`

**File:** `app/Models/Document.php` · **Table:** `documents` (`:40`) · **Traits:** `CompanyableTrait`, `HasUploads`, `Loggable`, SoftDeletes (`:19-26`).

A **document** is the actual controlled artifact (policy, procedure, register, evidence file). Files are attached via `HasUploads` and tracked in `action_logs`.

**Key columns** (`$fillable :42-62`, `$casts :68-77`, `$rules :79-99`):

| Column | Meaning / notes |
|---|---|
| `name` | Display name (`getDisplayNameAttribute :244-247`). |
| `company_id`, `owner_id`, `created_by` | Ownership; company scoping via `CompanyableScope`. |
| `document_type_id` | FK → `DocumentType`. |
| `document_framework_id` | FK → `DocumentFramework` (which framework this doc primarily belongs to; distinct from the many-to-many evidence links). |
| `document_number`, `reference`, `version` | Identifiers. |
| `status` | Enum `draft/active/in_review/obsolete/archived` (constants `:28-36`; `getStatusOptions :124-133`). Only `active` counts for coverage. |
| `document_area` | Enum `administration/it/cybersecurity` (rule `:89`; `documentAreaOptions :135-142`; column added `2026_05_22_130000…:48-52`). |
| `classification`, `retention_period`, `scope` | Governance metadata. |
| `issued_at`, `effective_at`, `next_review_at` | Lifecycle dates (`date` casts). Drive `scopeDueForReview :274-279`, `scopeOverdueForReview :281-285`, and coverage window (§5). |
| `control_url`, `summary`, `notes` | Links/notes (empty→null setters `:214-242`). |

**Relationships**

| Method | Kind | Target · FK / pivot |
|---|---|---|
| `company()` `:144-147` | belongsTo | `Company` · `company_id` |
| `owner()` `:149-152` | belongsTo (`withTrashed`) | `User` · `owner_id` |
| `type()` `:154-157` | belongsTo | `DocumentType` · `document_type_id` |
| `framework()` `:159-162` | belongsTo | `DocumentFramework` · `document_framework_id` |
| `frameworkRequirements()` `:164-169` | belongsToMany | `DocumentFrameworkRequirement` via `document_framework_requirement_document` (evidence pivot, §5) |
| `tenantServices()` `:171-177` | belongsToMany | `TenantService` via `document_tenant_service` (timestamps) |
| `documentAssignments()` `:179-182` | hasMany | `DocumentAssignment` · `document_id` |
| `documentAssignmentEvents()` `:184-187` | hasMany | `DocumentAssignmentEvent` · `document_id` |
| `tickets()` `:189-192` | hasMany | `Ticket` · `document_id` |
| `assetlog()` / `journal()` `:194-207` | hasMany | `Actionlog` (polymorphic `item_type = Document::class`) |

**Coverage scopes:** `currentForCoverage()` (`:287-299`) and `hasCoverageUpload()` (`:301-335`) — the "is this document live evidence?" gate consumed by requirement coverage (§5).

**Migration:** `2026_04_17_170000…:36-68` (FKs to companies/users/document_types/document_frameworks, all `nullOnDelete`, `:64-67`); `document_area` added later (`2026_05_22_130000`).

---

## 7. `DocumentType`

**File:** `app/Models/DocumentType.php` · **Table:** `document_types` (`:23`) · **Traits:** SoftDeletes + `TenantTemplateTrait` (`:19-20`).

A **classification bucket** for documents (Policy, Procedura, Registro, …). Same template/visibility regime as `DocumentFramework` (global master types + per-company types).

**Key columns** (`$fillable :39-48`, `$casts :50-55`): `name`, `slug` (auto-slugified `:103-116`, DB `unique` `2026_04_17_170000:29`), `description`, `sort_order`, `is_active`, `company_id`, `visibility_type` (`private/descendants/global`, rule `:34`), `created_by` (`$hidden :25`).

**Relationships:** `documents()` hasMany `Document` · `document_type_id` (`:73-76`); `company()` belongsTo `Company` (trait). **Scopes:** `active :85-88`, `ordered :90-93`. `isDeletable()` blocks deletion while documents reference it (`:78-83`).

**Migrations:** created `2026_04_17_170000…:26-34`; `company_id`+`visibility_type` `2026_04_17_200000…:40-60`; global system types seeded `2026_05_22_090628`.

---

## 8. `DocumentAssignment`

**File:** `app/Models/DocumentAssignment.php` · **Table:** `document_assignments` (`:45`) · **Traits:** `CompanyableTrait`, SoftDeletes (`:15-19`).

A **polymorphic lifecycle link** attaching a document to a subject (issued-to / applies-to / required-for / evidence-for), with approval workflow and renewal dates. Per the verified schema, `assignable_type` is currently only `App\Models\User`, but the model is built for 5 target types.

**Enums (constants):**
- `assignable_type` tokens `user/asset/location/supplier/customer` (`:21-25`), mapped to FQCNs via `assignableClassMap()` (`:171-180`); `tokenForAssignableClass()`/`classForAssignableToken()` convert (`:182-198`). **Note:** the DB stores the **full class name** (morph type), while the token forms are for UI/labels.
- `relation_type`: `issued_to/applies_to/required_for/evidence_for` (`:27-30`; `relationTypeOptions :127-135`).
- `status`: `planned/required/active/completed/expired/revoked` (`:32-37`; `statusOptions :137-147`).
- `approval_status`: `pending/submitted/in_review/approved/rejected` (`:39-43`; `approvalStatusOptions :149-158`; CSS label mapping `getApprovalStatusClassAttribute :255-264`).

**Key columns** (`$fillable :47-67`, `$casts :69-83`): `document_id`, `company_id` (both **required**, `:86-87`), `assignable_type`/`assignable_id`, `relation_type`, `status`, `approval_status`, `issuer_id`, `reviewer_id`, `reference_number`, lifecycle dates `issued_at/effective_at/expires_at/renewal_due_at/completed_at/revoked_at` (`date`) + `reviewed_at` (`datetime`), `notes`, `review_notes`.

**Relationships**

| Method | Kind | Target · FK |
|---|---|---|
| `document()` `:200-203` | belongsTo (`withTrashed`) | `Document` · `document_id` |
| `company()` `:205-208` | belongsTo | `Company` · `company_id` |
| `issuer()` `:210-213` | belongsTo (`withTrashed`) | `User` · `issuer_id` |
| `reviewer()` `:215-218` | belongsTo (`withTrashed`) | `User` · `reviewer_id` |
| `adminuser()` `:220-223` | belongsTo (`withTrashed`) | `User` · `created_by` |
| `events()` `:225-228` | hasMany | `DocumentAssignmentEvent` · `document_assignment_id` |
| `assignable()` `:230-233` | **morphTo** | `assignable_type` + `assignable_id` |

**Derived:** `assignable_display_name`/`assignable_url` switch on the concrete morph type (`:266-300`); `is_expiring` (renewal within 30 days, `:302-307`), `is_expired` (`:309-312`).

**Migrations:** base table `2026_04_20_130000…:11-42` (FKs: `document_id`/`company_id` `cascadeOnDelete`, `issuer_id` `nullOnDelete`, `:39-41`); `approval_status`/`reviewer_id`/`reviewed_at`/`review_notes` + `document_assignment_events` table added `2026_05_08_111000…:11-52`; hash-chain columns (`hash_algorithm`, `previous_hash`, `payload_hash`, `event_hash`) added to events `2026_05_08_115000…:11-27` (tamper-evident audit).

---

## 9. `TenantService`

**File:** `app/Models/TenantService.php` · **Table:** `tenant_services` (`:37`) · **Traits:** SoftDeletes only — **no** company/template global scope; scoped manually.

A **tenant's declared business service / activity**, classified by an ACN "macro area" (the NIS2 sector taxonomy) with an assessed relevance/impact. Documents, contracts, and assets are tagged with the services they pertain to.

**Enums (constants):**
- `macro_area` — 13 named constants (`MACRO_MONITORING_CONTROL` … `MACRO_OTHER_SERVICES_ACTIVITIES`, `:18-30`) plus a large flat ACN label map in `acnMacroAreaLabels()` (`:108-158`); the umbrella `production_goods_services` is excluded from the selectable list (`selectableAcnMacroAreaLabels :160-165`).
- `relevance` impact — `minimal/low/medium/high` (`IMPACT_*` `:32-35`; `acnImpactLabels :167-175`). Default relevance per macro-area in `defaultRelevanceByMacroArea()` (`:177-227`).

**Key columns** (`$fillable :39-49`, `$casts :51-56`, default `is_active=true` `:58-60`):

| Column | Meaning / notes |
|---|---|
| `tenant_id` | Owning tenant (**required**, FK cascade). |
| `company_id` | **`NULL` ⇒ service applies tenant-wide (all companies); set ⇒ scoped to one company** (migration comment `2026_06_18_120000…:13-14`). |
| `macro_area` | ACN sector key (see enums). Label via `macro_area_label`/`acn_macro_area_label` (`:311-319`). |
| `name`, `description` | Service name + description (empty→null setter `:364-367`). |
| `acn_subject_basis` | Free-text legal/subjection basis (empty→null `:369-372`; column `2026_06_08_090000`). |
| `relevance_override` | Manual impact override; when set it wins over the macro-area default (`assigned_relevance = relevance_override ?: pre_assigned_relevance`, `:340-343`). |
| `is_active`, `created_by` | Active flag / creator. |

**Relationships**

| Method | Kind | Target · pivot / FK |
|---|---|---|
| `tenant()` `:273-276` | belongsTo | `Tenant` · `tenant_id` |
| `company()` `:278-281` | belongsTo | `Company` · `company_id` |
| `documents()` `:283-287` | belongsToMany | `Document` via `document_tenant_service` (timestamps) |
| `contracts()` `:289-293` | belongsToMany | `CustomerContract` via `customer_contract_tenant_service` |
| `assets()` `:295-299` | belongsToMany | `Asset` via `asset_tenant_service` |
| `adminuser()` `:301-304` | belongsTo (`withTrashed`) | `User` · `created_by` |

**Manual scoping helpers** (replacing a global scope): `activeForCompanyId($companyId)` resolves the company's tenant via `TenantRecordGuard::companyTenantId`, then returns services where `company_id IS NULL OR company_id = $companyId`, active, ordered (`:234-252`); `validIdsForCompany()` is the write-side allow-list (`:254-271`).

**Migrations:** table + `document_tenant_service` + `customer_contract_tenant_service` created `2026_06_04_090000…:11-61`; `asset_tenant_service` `2026_06_16_120000`; `acn_subject_basis` `2026_06_08_090000`; `company_id` `2026_06_18_120000`; uniqueness re-scoped to include `company_id` (`tenant_id, company_id, macro_area, name, deleted_at`) `2026_06_25_090000…:39-42`.

---

## 10. Relationship graph (summary)

```
Company ──1:N── DocumentFramework(company_id, template-visibility scoped)
                   │  is_system_template / source_framework_id(self-FK) / source_pack_key
                   ├─1:N── DocumentFrameworkRequirement (document_framework_id)
                   │          │  parent_id (legacy self-FK) + children()
                   │          ├─M:N self ── document_framework_requirement_parents
                   │          │              (child_requirement_id, parent_requirement_id)
                   │          └─M:N ── Document ── document_framework_requirement_document
                   │                              (coverage_role: primary|supporting,
                   │                               notes, covered_at, created_by)
                   └─1:N── Document (document_framework_id)

DocumentType ──1:N── Document (document_type_id)          [template-visibility scoped]

Document (CompanyableScope)
   ├─1:N── DocumentAssignment (document_id) ──morphTo── User|Asset|Location|Supplier|Customer
   │            └─1:N── DocumentAssignmentEvent (hash-chained audit)
   ├─1:N── DocumentAssignmentEvent (document_id)
   └─M:N── TenantService ── document_tenant_service

Tenant ──1:N── TenantService (tenant_id; company_id NULL=tenant-wide)
                   ├─M:N── Document   (document_tenant_service)
                   ├─M:N── CustomerContract (customer_contract_tenant_service)
                   └─M:N── Asset      (asset_tenant_service)

DocumentFramework.compliance_domain ──(string key)──► ComplianceDomain.key
```

**Load-bearing invariants to remember:**
1. Template vs operational is `is_system_template` + `company_id` + `source_framework_id`/`source_pack_key`; operational rows are *copies*, coverage is per-company (§3).
2. `minimum_required_documents = 0` on a requirement means auto-`covered`; otherwise coverage counts only **primary** documents that are **`currentForCoverage`** (active, in-window, file present) (§5).
3. Hierarchy lives in **both** `parent_id` and the `document_framework_requirement_parents` pivot; always read via the merging accessors (§4).
4. `DocumentFramework`/`DocumentType` use template-visibility scoping (null-company globals are visible to all); `Document`/`DocumentAssignment` use standard company scoping; `TenantService` is scoped by hand (§0).

---

# CRUD & Association Flow + API Surface — Compliance/DMS Modules

**Repo root:** `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it`
All `file:line` citations below are relative to that root.

## 0. Two surfaces, and how each is authenticated

| Surface | Route file | Auth | Notes |
|---|---|---|---|
| **Web (HTML/session)** | `routes/web.php`, `routes/web/documents.php` | `middleware: ['auth']` (session) + CSRF | The full CRUD + association UI lives here. |
| **JSON API `/api/v1`** | `routes/api.php` | Group-level guard `auth:web,api` set in `app/Providers/RouteServiceProvider.php:75` | Live and functional — **not empty**. |

**Correction for the team ("the API was empty"):** The bare base URL `GET /api/v1` deliberately returns a 404 stub (`routes/api.php:19-26`), which is likely what created that impression. In fact this fork ships **custom** API controllers (`app/Http/Controllers/Api/Document*`, `Api/CategoriesController`) — these are *not* stock Snipe-IT; stock Snipe-IT has no document-framework/requirement/assignment concepts at all. Every `/api/v1` route is authenticated at the group level by `auth:web,api` (`RouteServiceProvider.php:74-80`), i.e. either a logged-in web session **or** a Passport personal-access-token `Authorization: Bearer …`. The `api` middleware *group* in `app/Http/Kernel.php:87-90` intentionally carries no `auth:api` itself — the guard is applied by the route group, not the Kernel group. Web-route files are loaded in `RouteServiceProvider.php:43-63` (note `routes/web/documents.php` is pulled in at line 50).

### Permission-slug reference (policies)

All controllers gate with `$this->authorize(<ability>, …)`. The base policy `app/Policies/SnipePermissionsPolicy.php` maps abilities to slugs `"{columnName}.{suffix}"` (`.view` for index/view — `:98-110`; `.create` — `:141`; `.edit` for update — `:151`; `.delete` — `:169-176`; `.files` / `.files.view` — `:123-130`). `columnName()` per model:

| Model | `columnName()` | So abilities resolve to |
|---|---|---|
| Document | `documents` (`app/Policies/DocumentPolicy.php:12-14`) | `documents.view/create/edit/delete`; plus custom `documents.requirements.map` (`DocumentPolicy.php:62-71`), `documents.restore` (`:73`), `documents.force_delete` (`:79`), `documents.files` / `documents.files.view` |
| DocumentFramework | `documentframeworks` (`app/Policies/DocumentFrameworkPolicy.php:13-15`) | `documentframeworks.view/create/edit/delete` |
| **DocumentFrameworkRequirement** | **`documentframeworks`** (`app/Policies/DocumentFrameworkRequirementPolicy.php:13-15`) | Shares the framework slug — **there is no separate requirement permission**; editing requirements needs `documentframeworks.edit` |
| DocumentType | `documenttypes` (`app/Policies/DocumentTypePolicy.php:10-12`) | `documenttypes.view/create/edit/delete` |
| Category | `categories` (`app/Policies/CategoryPolicy.php:7-9`) | `categories.view/create/edit/delete` |

`DocumentPolicy::before()` additionally hard-gates every document ability through `ComplianceDomainAccess` + `DocumentAreaAccess` (`DocumentPolicy.php:16-30`).

---

## 1. Create a `document_framework`

There are **three distinct creation paths**; only the first makes a blank framework.

### 1a. Blank framework (web) — most direct
- **Form:** `GET /documentframeworks/create` → `documentframeworks.create` → `DocumentFrameworksController@create` (`routes/web.php:100`; `app/Http/Controllers/DocumentFrameworksController.php:32-40`) — permission `documentframeworks.create`.
- **Save:** `POST /documentframeworks` → `documentframeworks.store` → `DocumentFrameworksController@store` (`web.php:100`; controller `:52-67`).
- Validation: `app/Http/Requests/StoreDocumentFrameworkRequest.php`. Key fields: `name`, `slug` (auto-slugged from name if blank — `:31`), `compliance_domain`, `framework_type`, `status` (required), `visibility_type` (`private|descendants|global`, required — `:58`), `company_id`. `company_id`+`visibility_type` are normalized via `Company::normalizeTemplateOwnership()` (`:23-26`); `visibility_type=global` requires `Tenant::canCurrentUserUseGlobalTenantContext()` (`:71-77`).
- **This creates an empty framework with 0 requirements** (`store` only fills + saves; no requirement cloning — `controller:56-62`). To get an operational, requirement-populated framework you normally use path 1b or 1c instead.

### 1b. Clone/instantiate from a **system template** (the real "populate with requirements" path)
This is **not** on the framework controller — it is the compliance-pack apply flow, **superadmin-only**, under the `admin` prefix (`web.php:283` group `middleware:['auth','authorize:superadmin']`, and `ComplianceFrameworkPacksController::authorizeGlobalPackManagement()` re-checks `isSuperAdmin()` — `app/Http/Controllers/ComplianceFrameworkPacksController.php:227-230`):

| Action | Method + URI | Route name | Controller@method |
|---|---|---|---|
| Pack dashboard | `GET /admin/compliance-framework-packs` | `settings.compliance_framework_packs.index` | `ComplianceFrameworkPacksController@index` (`web.php:284`) |
| Pack detail | `GET /admin/compliance-framework-packs/{packKey}` | `…show` | `@show` (`web.php:289`) |
| Install/refresh **system** master template | `POST …/{packKey}/system` | `…system.apply` | `@applySystem` (`web.php:295`; controller `:58-88`) |
| Instantiate **operational** copy for one tenant | `POST …/{packKey}/tenants/{tenant}` | `…tenants.apply` | `@applyTenant` (`web.php:303`; controller `:90-128`) |
| Bulk-instantiate for many tenants | `POST …/{packKey}/tenants/bulk` | `…tenants.bulk_apply` | `@applyTenantsBulk` (`web.php:299`; controller `:166-225`) |

`applyTenant` delegates to `ComplianceFrameworkPackTenantUpdater::applyPack()` which is what produces the per-company operational `document_framework` (with `source_framework_id` + `source_pack_key`) and copies its requirements. This is how the verified "CodyCloud NIS2 Allegato2 / Italway NIS2 Allegato1" operational frameworks were created.

### 1c. Import from a consultant export file (web)
- **Form:** `GET /documentframeworks/import` → `documentframeworks.import` → `@importForm` (`web.php:88`; controller `:42-50`).
- **Upload:** `POST /documentframeworks/import` → `documentframeworks.import.store` → `@import` (`web.php:89`; controller `:69-108`). Takes a `file` (≤10 MB) + `company_id` + `visibility_type`, parsed by `ConsultantFrameworkTransfer::import()`; creates the framework **and its requirements** in one shot (`controller:93-107`). Permission `documentframeworks.create`.
- Reverse (`export`): `GET /documentframeworks/{documentframework}/export/{csv|xlsx|docx}` → `@export` (`web.php:92-94`).

### 1d. Blank framework (API)
- `POST /api/v1/documentframeworks` → `api.documentframeworks.store` → `Api\DocumentFrameworksController@store` (`routes/api.php:214-226`; `app/Http/Controllers/Api/DocumentFrameworksController.php:132-146`). Same `StoreDocumentFrameworkRequest`. **Also creates a blank framework — no requirement cloning on the API.** Full resource: index/show/store/update/destroy + `restore` (`api.php:200-204`) + `selectlist` (`api.php:195-199`).

---

## 2. Create / edit a requirement within a framework

### Web
- **Create form:** `GET /documentframeworks/{documentframework}/requirements/create` → `documentframeworkrequirements.create` → `DocumentFrameworkRequirementsController@create` (`web.php:96`; `app/Http/Controllers/DocumentFrameworkRequirementsController.php:61-71`).
- **Create save:** `POST /documentframeworks/{documentframework}/requirements` → `documentframeworkrequirements.store` → `@store` (`web.php:97`; controller `:73-96`). Both gate on `authorize('update', $documentframework)` → **`documentframeworks.edit`**.
- **Edit form / update / show / destroy / index** come from `Route::resource('documentframeworkrequirements', …)->except(['create','store'])` (`web.php:106`):
  - `GET /documentframeworkrequirements` → `.index` (`@index :24-59`)
  - `GET /documentframeworkrequirements/{documentframeworkrequirement}` → `.show` (`@show :98-123`)
  - `GET /documentframeworkrequirements/{documentframeworkrequirement}/edit` → `.edit` (`@edit :125-139`)
  - `PUT/PATCH /documentframeworkrequirements/{documentframeworkrequirement}` → `.update` (`@update :141-161`)
  - `DELETE /documentframeworkrequirements/{documentframeworkrequirement}` → `.destroy` (`@destroy :349-366`; blocked if it still has evidence docs — `:359-361`)
- **Bulk + restore** (separate group, `web.php:101-105`): `POST /documentframeworkrequirements/bulk/edit` (`.bulk.edit`), `POST …/bulk/update` (`.bulk.update`), `POST …/{id}/restore` (`.restore`).
- Validation: `app/Http/Requests/StoreDocumentFrameworkRequirementRequest.php`. Required: `code`, `title`, `minimum_required_documents`, `delegation_level`, `risk_level` (`:28-40`); `code` must be unique within the framework (`:100-112`). Parent hierarchy is written to `document_framework_requirement_parents` via `parents()->sync()` — controller `syncParentRequirements()` (`:427-442`), guarded against cycles (`:573-618`). For NIS2 domains `risk_level` is forced to `not_applicable` (`request:74-76`).

### API
- Full resource `POST/PUT/PATCH/DELETE /api/v1/documentframeworkrequirements` → `Api\DocumentFrameworkRequirementsController` (`api.php:228-239`) + `restore` (`api.php:207-211`). **The API `store` takes the target framework in the request body** (`document_framework_id`, required — validated in the same FormRequest), not in the URI (`Api/DocumentFrameworkRequirementsController.php:114-118`). Same `documentframeworks.edit` gate.

---

## 3. Create a document (incl. file upload)

### Web
- **Form:** `GET /documents/create` → `documents.create` → `Documents\DocumentsController@create` (`routes/web/documents.php:39-42`; `app/Http/Controllers/Documents/DocumentsController.php:79-93`). Permission `documents.create`.
- **Save:** `POST /documents` → `documents.store` → `@store` (`documents.php:39`; controller `:95-121`).
- Validation `app/Http/Requests/StoreDocumentRequest.php`. Core fields at `:35-54` (`name` required, `status` required, `company_id`, `owner_id`, `document_type_id`, `document_framework_id`, dates, `document_area`, `classification`, etc.). Note `document_framework_id` **cannot be a system template** and must be tenant-applicable (`request:92-102`).
- The `store` transaction does four things (`controller:104-110`): `persistDocument()` → `syncRequirementMappings()` (§4) → `syncTenantServices()` → `persistInlineAssignment()` (§5). File upload happens **after** the transaction (`:115`).

**File upload — reuse question:** The document form's inline `file[]` upload does **not** use the shared `UploadedFilesController`/trait. It uses a **private** `storeUploadedFiles()` on this controller (`Documents/DocumentsController.php:187-208`), which instantiates `UploadFileRequest` and calls `->handleFile()` + `$document->logUpload(...)` with `FileIntegrity` metadata — the same low-level storage+audit path the standalone files panel uses, so uploads render identically. Fields: `file[]` and `file_notes` (validated in `StoreDocumentRequest.php:65-70`). The same private method is reused by bulk attach (`bulkUpdate`, `:392-396`).

There is **no** separate "document files" route (web or API) — a `grep` of `routes/` for document file/download/show routes returns nothing. Files are only attached through the document create/update/bulk forms.

### API
- `POST /api/v1/documents` → `api.documents.store` → `Api\DocumentsController@store` (`api.php:735-744`; `app/Http/Controllers/Api/DocumentsController.php:173-188`). Update is exposed explicitly as `PATCH` **and** `PUT /api/v1/documents/{document}` (`api.php:725-726` → `@update :190-203`); the resource itself excludes create/edit/update (`api.php:742`). Also `index`, `show`, `destroy`, `{document}/history`, `{document}/force-delete` (`api.php:710-744`).
- **The API `store`/`update` do NOT accept file uploads** — neither method calls `storeUploadedFiles` (compare `Api/DocumentsController.php:173-203` — only `fill` + save + `syncRequirementMappings`). File attachment is **web-only**.

---

## 4. Associate a document to requirements — the evidence pivot

**This is the single most important clarification: there is NO dedicated "attach document to requirement" endpoint.** The pivot table `document_framework_requirement_document` is written **only** as a side-effect of the **Document store/update** endpoints, document-centric, via a full `sync()`.

- Writer (web): `Documents\DocumentsController::syncRequirementMappings()` (`app/Http/Controllers/Documents/DocumentsController.php:607-639`).
- Writer (API): identical logic in `Api\DocumentsController::syncRequirementMappings()` (`app/Http/Controllers/Api/DocumentsController.php:238-270`).
- Relationship: `Document::frameworkRequirements()` `belongsToMany(... 'document_framework_requirement_document')->withPivot(['coverage_role','notes','covered_at','created_by'])` (`app/Models/Document.php:166-167`; mirror on `app/Models/DocumentFrameworkRequirement.php:173-174`).

**Inputs (sent to `POST /documents` or `PUT /documents/{document}`, or the API equivalents):**
- `primary_requirement_ids[]` — written with `coverage_role = 'primary'` (`Document::COVERAGE_PRIMARY`, `Document.php:64`).
- `supporting_requirement_ids[]` — written with `coverage_role = 'supporting'` (`Document.php:66`).
- `requirement_evidence[<requirementId>][covered_at]` (Y-m-d) and `[notes]` — per-requirement pivot metadata; `covered_at` defaults to `now()` if omitted, `created_by` is stamped to the actor (`controller:618-635`).

**Semantics & guards:**
- It runs **only if** the request "submitted a mapping" — `StoreDocumentRequest::mappingSubmitted()` returns true when any of `primary_requirement_ids` / `supporting_requirement_ids` / `requirement_evidence` keys are present (`StoreDocumentRequest.php:177-182`). If none are present, existing mappings are left untouched.
- It is a **full `sync()`** (`controller:638`) — the submitted set *replaces* the document's entire mapping, so a requirement omitted from the payload is **detached**.
- Requires the `mapRequirements` policy → **`documents.requirements.map`** (`controller:613`; `DocumentPolicy.php:62-71`). The request also fails validation if mapping is submitted without that permission (`StoreDocumentRequest.php:140-142`).
- The document must have a `document_framework_id`, and every requirement ID must belong to **that** framework (`StoreDocumentRequest.php:117-138`). A requirement from another framework, or IDs with no framework set, is rejected.

**UI entry point (how a user reaches this from a requirement):** the requirements matrix / requirement view links to `GET /documents/create?document_framework_requirement_id=<id>` (or the documents index pre-selects it — `Documents/DocumentsController.php:68-70`), and the create form pre-checks that requirement; on save the same `syncRequirementMappings` writes the pivot. So the *conceptual* "add this document as evidence for requirement X" is implemented as "create/edit a document and include X in `primary_requirement_ids`".

**API coverage:** ✅ Fully supported — the evidence pivot **is** writable over `/api/v1` because `Api\DocumentsController@store/@update` call the same `syncRequirementMappings` (`api.php:735-744` + `:725-726`). Send `primary_requirement_ids[]` / `supporting_requirement_ids[]` / `requirement_evidence[...]` in the JSON body.

---

## 5. Assign a document (`document_assignments`) + approval/review lifecycle

Two ways to create an assignment; **all writes are WEB-only**.

### 5a. Inline with the document
When document create/update is submitted with assignment fields, `persistInlineAssignment()` runs inside the same transaction (`Documents/DocumentsController.php:662-690`). "Submitted" = `save_assignment=1` **or** any `assignment_*` field carrying a value (`DocumentAssignmentManager::submissionRequested()` — `app/Support/Documents/DocumentAssignmentManager.php:56-87`).

### 5b. Dedicated assignment endpoints (web-only)
Defined in `routes/web/documents.php:28-35` → `Documents\DocumentAssignmentsController`, all gated `authorize('update', $document)` → **`documents.edit`**:

| Action | Method + URI | Route name | Controller@method |
|---|---|---|---|
| Create | `POST /documents/{document}/assignments` | `documents.assignments.store` | `@store` (`DocumentAssignmentsController.php:24-50`) |
| Edit form | `GET /documents/{document}/assignments/{documentAssignment}/edit` | `documents.assignments.edit` | `@edit :52-58` |
| Update | `PUT /documents/{document}/assignments/{documentAssignment}` | `documents.assignments.update` | `@update :60-89` |
| Delete | `DELETE /documents/{document}/assignments/{documentAssignment}` | `documents.assignments.destroy` | `@destroy :91-108` |
| "Evidence requests" list | `GET /documents/evidence-requests` | `documents.evidence_requests.index` | `@index :17-22` |

### Fields & lifecycle (`DocumentAssignmentManager`)
Validation rules `DocumentAssignmentManager::rules()` (`:89-110`): `assignable_type` (required, in `assignableClassMap`), `assignable_id` (required), `relation_type` (required), `status` (required), `approval_status` (required), plus optional `issuer_id`, `reviewer_id`, `reference_number`, and dates `issued_at/effective_at/expires_at/renewal_due_at/completed_at/revoked_at/reviewed_at`, `notes`, `review_notes`. Form inputs may be `assignment_`-prefixed or bare (`assignmentField()` — `:399-406`).

Enumerations (`app/Models/DocumentAssignment.php`):
- **assignable_type** (`:21-25`): `user`, `asset`, `location`, `supplier`, `customer` (currently only User rows exist in prod per the schema notes, but the model supports all five).
- **relation_type** (`:27-30`): `issued_to`, `applies_to`, `required_for`, `evidence_for`.
- **status** (`:32-37`): `planned`, `required`, `active`, `completed`, `expired`, `revoked`.
- **approval_status** (`:39-43`): `pending`, `submitted`, `in_review`, `approved`, `rejected`.

**Approval/review automation** (`DocumentAssignmentManager`):
- When `approval_status` is set to `approved` or `rejected`, `reviewer_id` auto-fills to the actor if unset (`reviewerId()` — `:455-469`) and `reviewed_at` auto-stamps to now (`reviewedAt()` — `:471-482`).
- Cross-field date coherence is enforced (`validateDateCoherence()` — `:351-388`): e.g. `effective_at ≥ issued_at`, `expires_at ≥ effective_at`, `renewal_due_at ≤ expires_at`, etc.
- Tenant integrity: assignable target, issuer and reviewer must share the document's tenant (`validateForDocument()` / `validateUserTenant()` — `:154-192`, `:432-453`).

**Tamper-evident audit:** every create/update/delete writes a hash-chained `document_assignment_events` row via `logAssignmentEvent()` (`:239-260`) — each event stores `previous_hash`/`payload_hash`/`event_hash` (SHA-256 chain, `assignmentEventHashes()` — `:306-329`). On update, an `approval_status` change is logged as `EVENT_APPROVAL_STATUS_CHANGED`, otherwise `EVENT_UPDATED` (`DocumentAssignmentsController.php:78-83`). A parallel `Actionlog` entry is written by `logAssignmentAction()` (`:219-237`).

### API coverage — read-only
- `GET /api/v1/documentassignments` → `api.documentassignments.index` → `Api\DocumentAssignmentsController@index` (`api.php:728-733`; `app/Http/Controllers/Api/DocumentAssignmentsController.php:18-138`). Rich filtering (by document, assignee, status, approval_status, review_status, delegated_evidence, etc.). Gate: `authorize('index', Document::class)` → `documents.view`.
- **There is NO API store/update/destroy for assignments.** Creating, editing, approving/rejecting, or deleting an assignment is **web-only**.

---

## 6. Categories CRUD (the `/categories` the user asked about) + Document Types

### Categories (web) — `app/Http/Controllers/CategoriesController.php`
`Route::resource('categories', CategoriesController::class, ['parameters'=>['category'=>'category_id']])` (`routes/web.php:68-70`) + bulk delete (`web.php:72`):

| Action | Method + URI | Route name | Controller@method | Perm |
|---|---|---|---|---|
| List | `GET /categories` | `categories.index` | `@index :35-41` | `categories.view` |
| Create form | `GET /categories/create` | `categories.create` | `@create :51-59` | `categories.create` |
| Store | `POST /categories` | `categories.store` | `@store :69-98` | `categories.create` |
| Show | `GET /categories/{category_id}` | `categories.show` | `@show :225-243` | `categories.view` |
| Edit form | `GET /categories/{category_id}/edit` | `categories.edit` | `@edit :111-118` | `categories.edit` |
| Update | `PUT/PATCH /categories/{category_id}` | `categories.update` | `@update :131-186` | `categories.edit` |
| Destroy | `DELETE /categories/{category_id}` | `categories.destroy` | `@destroy :197-211` | `categories.delete` |
| Bulk delete | `POST /categories/bulk/delete` | `categories.bulk.delete` | `BulkCategoriesController@destroy` | — |

Relevant to the compliance module, the category form persists the NIS2 fields `nis_inventory_required` + `nis_inventory_scope` (`@store :82-83`, `@update :150-151`) and `category_type`/`fieldset_id`. `@update` has two **opt-in** propagations: `apply_fieldset_to_models` (fills models missing a fieldset — `:165-167`) and `apply_nis_to_assets` (stamps `nis_relevant`/`nis_inventory_scope` onto the category's assets — `:172-178`). `category_type` is locked once the category has items (`:137-139`). Validation/image handling via `ImageUploadRequest`.

### Categories (API) — `app/Http/Controllers/Api/CategoriesController.php`
`Route::resource('categories', Api\CategoriesController::class, …)` with `index/show/update/store/destroy` (`api.php:150-162`, params `category_id`) + `GET /api/v1/categories/selectlist/{category_type?}` (`api.php:139-146`). Standard REST; **no `restore`** on the API. Same `categories.*` perms.

### Document Types
- **Web:** `Route::resource('documenttypes', DocumentTypesController::class)` (`web.php:81`) + `POST /documenttypes/{id}/restore` (`web.php:78`). Full CRUD in `app/Http/Controllers/DocumentTypesController.php` (validated by `StoreDocumentTypeRequest`), perms `documenttypes.view/create/edit/delete`.
- **API:** full resource `index/show/store/update/destroy` (`api.php:257-267`) + `selectlist` (`api.php:245-249`) + `restore` (`api.php:252-254`).

---

## 7. WEB-only vs API — capability matrix

| Capability | Web | `/api/v1` | Evidence |
|---|---|---|---|
| Framework CRUD (blank) | ✅ | ✅ | `web.php:100` / `api.php:214-226` |
| Framework **clone from system template** (populate requirements) | ✅ superadmin | ❌ | `web.php:295-303` (pack apply); no API |
| Framework import / export / requirements-matrix / mark-reviewed / purge | ✅ | ❌ | `web.php:88-95`, `:90-92` |
| Requirement CRUD (+ parent hierarchy, bulk) | ✅ | ✅ (no bulk) | `web.php:96-106` / `api.php:228-239` |
| Document CRUD | ✅ | ✅ | `documents.php:39-42` / `api.php:735-744`,`725-726` |
| Document **file upload** | ✅ (inline `file[]`) | ❌ | `Documents/DocumentsController.php:187-208`; API store has none |
| **Evidence pivot** (document ↔ requirement, coverage_role) | ✅ | ✅ | `Documents/DocumentsController.php:607-639` / `Api/DocumentsController.php:238-270` |
| Assignment **create/update/delete** + approval/review | ✅ | ❌ | `documents.php:28-35`; API is index-only |
| Assignment **read/list** | ✅ | ✅ (read-only) | `api.php:728-733` |
| Categories CRUD | ✅ (+restore, bulk) | ✅ (no restore) | `web.php:68-72` / `api.php:150-162` |
| Document Types CRUD | ✅ | ✅ | `web.php:78-81` / `api.php:245-267` |
| Compliance-pack apply (system/tenant/bulk) | ✅ superadmin | ❌ | `web.php:284-309` |

**Net:** The API covers the *record* CRUD for frameworks, requirements, documents, document-types, categories, and (uniquely useful) the **evidence pivot** via the document store/update. The API does **not** cover: file attachment, the assignment write lifecycle, the template-clone/pack-apply flow, or framework import/export/matrix/review — all web-only.

---

## Key source files (absolute paths)

Routes
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/routes/web.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/routes/web/documents.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/routes/api.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Providers/RouteServiceProvider.php`

Web controllers
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/Documents/DocumentsController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/Documents/DocumentAssignmentsController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/DocumentFrameworksController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/DocumentFrameworkRequirementsController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/DocumentTypesController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/CategoriesController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/ComplianceFrameworkPacksController.php`

API controllers
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/Api/DocumentsController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/Api/DocumentAssignmentsController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/Api/DocumentFrameworksController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/Api/DocumentFrameworkRequirementsController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/Api/DocumentTypesController.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Controllers/Api/CategoriesController.php`

Requests / support / policies
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Requests/StoreDocumentRequest.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Requests/StoreDocumentFrameworkRequest.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Http/Requests/StoreDocumentFrameworkRequirementRequest.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Support/Documents/DocumentAssignmentManager.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Models/Document.php`, `app/Models/DocumentAssignment.php`, `app/Models/DocumentFrameworkRequirement.php`
- `/var/www/3232d3a6-1743-4b66-b09c-edf87bec2cf8/asset.codycloud.it/app/Policies/{SnipePermissionsPolicy,DocumentPolicy,DocumentFrameworkPolicy,DocumentFrameworkRequirementPolicy,DocumentTypePolicy,CategoryPolicy}.php`

---

# Compliance Packs — Available Catalogs & How to Apply One to a Company

*Module reference for the CodyCloud team. All paths under `asset.codycloud.it`. Live-state figures verified read-only on 2026-08-12.*

---

## 0. Where packs live and how they reach the app

A "pack" is a pure-PHP data structure (framework metadata + a list of requirements). Packs are **not** in the DB and **not** seeded by migration — they are compiled at runtime by one factory and exposed through one config key:

- `config/compliance_frameworks.php:7` → `return App\Support\Compliance\ComplianceFrameworkPackCatalog::make();`
- Everything reads them via `config('compliance_frameworks.packs')` (e.g. `ComplianceFrameworkInstaller.php:19`, `:103`).

`ComplianceFrameworkPackCatalog::make()` (`app/Support/Compliance/ComplianceFrameworkPackCatalog.php:7`) returns:
```
['source_checked_at'=>…, 'source_registers'=>…, 'nis2_country_overlays'=>…, 'packs'=>$packs]   // :3287-3292
```
Only the `packs` sub-array is installable.

### 0.1 The build is tricky — most of the `$packs` you see in the file are discarded
The file defines dozens of intermediate packs (EU-locale NIS2/GDPR/AI-Act variants, `nis2_en`, `gdpr_en`, `nis2_eu_it`, …), but at `:1227` the whole `$packs` variable is **reassigned** to just the two Allegato packs; the previous contents are saved to `$ccLegacyPacks` (`:1225`) and only two of them are pulled back later (`gdpr_eu` `:3284`, `ai_act_it` `:3285`). Net result: **the shipped catalog is exactly 7 packs.** Do not assume the many blocks above line 1227 are available — they are not.

Two of the seven load their requirements from external files via `self::packRequirements()` (`:3405-3416`, `require __DIR__.'/Packs/<file>'`):
- `app/Support/Compliance/Packs/nis2_it_allegato_1.php` (returns 87 requirement rows)
- `app/Support/Compliance/Packs/nis2_it_allegato_2.php` (returns 116 requirement rows)

The other five are defined inline in the catalog.

---

## 1. The catalog — packs available to load

`config('compliance_frameworks.packs')` resolves to these **7 packs** (verified by loading the config and counting requirements). Default pack version is `2026.05.21.1` (`:9`); the AI-Act pack carries its own `2026.05.14.1` (`:10`).

| `pack_key` | Framework name | Domain | Jurisdiction | Locale | #Reqs | Requirements source | Catalog def |
|---|---|---|---|---|---|---|---|
| `nis2_it_allegato_1` | NIS2 IT - Allegato 1 | `nis2` | IT/EU | it-IT | **87** | `Packs/nis2_it_allegato_1.php` | `:1228` (loader `:1249`) |
| `nis2_it_allegato_2` | NIS2 IT - Allegato 2 | `nis2` | IT/EU | it-IT | **116** | `Packs/nis2_it_allegato_2.php` | `:1251` (loader `:1272`) |
| `dl81_it` | D.Lgs. 81/2008 - Salute e sicurezza sul lavoro | `dl81` | IT | it-IT | **25** | inline | `:1298` |
| `iso27001_it` | ISO/IEC 27001:2022 - ISMS | `iso27001` | IT/EU | it-IT | **68** | inline | `:1721` |
| `iso9001_it` | ISO 9001:2015 - Sistema di gestione per la qualità | `iso9001` | IT/EU | it-IT | **35** | inline | `:2731` |
| `gdpr_eu` | GDPR - Evidenze documentali | `gdpr` | EU | it-IT | **6** | inline (`:582`, locale it-IT `:584`) | re-added `:3284` |
| `ai_act_it` | AI Act UE - Evidenze e classificazione | `ai_act` | EU | it-IT | **16** | inline (`:1091`, locale `:1093`) | re-added `:3285` |

Every pack also carries provenance (`source_register` / `source_register_key`, wired at `:1032-1039`, `:1277-1280`, `:3278-3285`) pointing into `$sourceRegisters` (`:13-87`) with official-source URLs and a `scope` that drives jurisdiction matching (see §4.1).

This maps 1:1 to the 7 system-template frameworks in the DB (`document_frameworks.is_system_template=1`, ids 106–112):

```
106 dl81_it(25)  107 iso27001_it(68)  108 iso9001_it(35)  109 nis2_it_allegato_1(87)
110 nis2_it_allegato_2(116)  111 gdpr_eu(6)  112 ai_act_it(16)
```

---

## 2. The instantiation mechanism (system template → per-company operational framework)

### 2.1 The single write path: `ComplianceFrameworkInstaller::installPack()`
`app/Support/Compliance/ComplianceFrameworkInstaller.php:162`. Every install ultimately runs here. It:

1. Stamps the framework row with ownership + provenance (`:176-185`): `company_id`, `visibility_type`, `is_system_template`, **`source_pack_key = $packKey`** (`:179`), **`source_pack_version`** (`:180`), `locale`, and **`source_framework_id`** — set to the matching system template's id via `systemFrameworkIdForPack()` (`:183-185`, `:321-328`) **only when `link_system_source` is true** (i.e. for operational copies, not for the template itself).
2. Finds an existing framework by **`source_pack_key` OR `slug`, scoped to the same `company_id`** (`:187-200`). This match rule is the anti-duplicate guard and is reused by sync/purge.
3. Create / update / skip (`:202-224`): if absent → create; if present and `update_existing` → overwrite; else skip but **backfill only blank provenance columns** (`:210-223`) — it never clobbers tenant edits.
4. Auto-creates any **global document types** named by `default_document_type_name` (`:230`, `:357-389`), then upserts each requirement keyed by `(document_framework_id, code)` (`:232-265`) and re-links the requirement hierarchy (`parent_id` + `document_framework_requirement_parents` pivot, `:267-293`).

Ownership invariants are enforced by `assertOwnershipOptions()` (`:298-319`): a system template **must** be global + company-less; a tenant/company pack **must** have a company id and **must not** be global.

### 2.2 Three public entry points into `installPack`

| Method | Produces | Key options | File:line |
|---|---|---|---|
| `installSystemPack()` | the **global master template** (`company_id=NULL`, `is_system_template=true`, `VISIBILITY_GLOBAL`, `link_system_source=false`) | — | `:128-139` |
| `installCompanyPack()` | an **operational company copy** (requires `companyId`, forbids global, `is_system_template=false`, `link_system_source=true`) | throws if `companyId` null (`:143`) or visibility global (`:147`) | `:141-160` |
| `bootstrapTenant()` | operational copies on the **tenant root company** for a set of locale/jurisdiction-compatible packs | resolves root company (`:76`), locale (`:89`), pack set (`:90`), then loops `installPack` with `is_system_template=false`, `link_system_source=true` (`:109-117`) | `:74-126` |

`installSystemPack` is how the 7 templates (ids 106–112) were created; `bootstrapTenant`/`installCompanyPack` is how per-company operational frameworks are created.

### 2.3 Who calls these — the four surfaces

**(a) Artisan `snipeit:install-compliance-frameworks`** — `app/Console/Commands/InstallComplianceFrameworks.php:13-19`
```
snipeit:install-compliance-frameworks {pack=all}
    {--company_id=} {--tenant_id=} {--visibility=global} {--update-existing} {--dry-run}
```
- No `--tenant_id`, no `--company_id` → installs pack(s) as **system templates** (`installSystemPack`, `:108`). This is the command that seeded ids 106–112.
- `--tenant_id=N` → `bootstrapTenant` on that tenant's root company, restricted to `availablePackKeys(locale, jurisdiction)` (`:28-63`).
- `--company_id=N` (no tenant) → `installCompanyPack` onto that exact company (`:70-76`, `:93-109`). **This is the only path that bypasses the jurisdiction gate and can target a non-root company.**
- **Records no audit event** (see §3.3). Supports `--dry-run`.

**(b) Artisan `snipeit:sync-compliance-framework-packs`** — `app/Console/Commands/SyncComplianceFrameworkPacks.php:13-16` (the diff/merge tool; see §3).

**(c) Tenant create/config UI** — `TenantsController.php:159-164`, `:772-775`. When the operator ticks `bootstrap_compliance_frameworks`, it calls `ComplianceFrameworkPackTenantUpdater::applyAvailablePacks($tenant, actorId)` (`ComplianceFrameworkPackTenantUpdater.php:16-39`), which loops every locale/jurisdiction-compatible pack through `applyPack()`. **No scheduler runs these** — `grep` of the console kernel shows no cron entry; installs are on-demand only.

**(d) Pack-management dashboard** (super-admin) — `ComplianceFrameworkPacksController.php`, routes `routes/web.php:284-309`:
- `applySystem` (`:58-88`) → `installSystemPack(..., updateExisting=true)`.
- `applyTenant` (`:90-128`) and `applyTenantsBulk` (`:166-225`) → `ComplianceFrameworkPackTenantUpdater::applyPack()`.
- `purgeTenant` (`:130-164`) → purger (see §3.4).
- Every action is gated by `abort_unless(isSuperAdmin(), 403)` (`:227-230`).

`ComplianceFrameworkPackTenantUpdater::applyPack()` (`ComplianceFrameworkPackTenantUpdater.php:41-131`) is the safe, audited workhorse behind the UI: it validates `locale` (`:56`) and jurisdiction membership (`:64`), then either **bootstraps** a missing framework (`:84-89`) or **merge-adds only missing requirements** (`:90-106`), and records a pack event (`:108-121`).

### 2.4 How CodyCloud got Allegato 2 and Italway got Allegato 1 (live state)

Verified operational frameworks (`is_system_template=0`):

| id | company | tenant | name | slug | `source_pack_key` | `source_framework_id` | #reqs |
|---|---|---|---|---|---|---|---|
| 2 | 1 CodyCloud | 1 | D.Lgs. 81/2008 | `dlgs-81-2008` | **NULL** | NULL | 0 |
| 4 | 1 CodyCloud | 1 | NIS2 IT - Allegato 2 | **`nis2`** | **NULL** | NULL | 116 |
| 104 | 100 Italway | 6 | NIS2 IT - Allegato 1 | `nis2-it-allegato-1` | `nis2_it_allegato_1` | NULL | 87 |

- **Italway's Allegato 1 (id 104)** was created **through the pack mechanism** — `source_pack_key='nis2_it_allegato_1'`, `source_pack_version=2026.05.21.1`. It is pack-linked and will be recognised by sync/dashboard.
- **CodyCloud's Allegato 2 (id 4) is NOT a pack instance.** Its slug is `nis2` and `source_pack_key` is NULL. It is the **hand-built, human-validated** framework that the `nis2_it_allegato_2` pack was *extracted from* — the catalog says so verbatim: *"Bootstrap requisiti NIS2 Italia Allegato 2 dal framework Codycloud validato"* (`ComplianceFrameworkPackCatalog.php:1257`). The pack file `Packs/nis2_it_allegato_2.php` is that export. Consequence: because sync/updater match on `source_pack_key` **or** `slug='nis2-it-allegato-2'` (`ComplianceFrameworkPackSync.php:78-81`), id 4 matches **neither** — it is invisible to the pack tooling and re-applying the pack would create a **duplicate**, not update id 4 (see §4.3).

---

## 3. Pack UPDATE / re-sync

### 3.1 Version + diff model — `ComplianceFrameworkPackSync`
`app/Support/Compliance/ComplianceFrameworkPackSync.php`. Pack version = `pack['pack_version']` (fallback `framework.version`) (`:359-362`). A framework stores the version it was installed at in `document_frameworks.source_pack_version` (column added by `database/migrations/2026_05_08_113000_add_compliance_pack_version_to_document_frameworks.php:14`; `source_pack_key`/`source_framework_id`/`locale` by `2026_05_07_130000_...:32,:36,:40`).

`diff()` (`:85-124`) compares a live framework against the pack and classifies it via `status()` (`:346-357`):
- **`modified`** — a shipped framework field or an existing requirement field differs from the pack (a **conflict** = tenant edit). Fields compared: `FRAMEWORK_FIELDS` (`:15-31`) and `REQUIREMENT_FIELDS` (`:33-51`).
- **`outdated`** — no conflicts, but requirements are missing **or** `source_pack_version ≠ pack_version`.
- **`current`** — matches.

### 3.2 The safe merge — never overwrites tenant edits
`mergeMissingRequirements()` (`:126-212`) runs in a transaction and **only inserts requirement codes listed in `missing_requirements`** (`:142-166`); it never edits an existing requirement. After merging, `refreshSourceMetadataIfClean()` (`:311-344`) bumps `source_pack_version` (and backfills `source_framework_id`) **only if `conflicts_count==0 && missing==0`** — so a clean framework silently advances to the new version, while a conflicted one is left for manual review.

CLI:
```
php artisan snipeit:sync-compliance-framework-packs {pack|all} [--tenant_id=N] [--apply]
```
Without `--apply` it prints a dry diff (`SyncComplianceFrameworkPacks.php:139-140`, `:203-219`). With `--apply`:
- **System scope** (no `--tenant_id`): `installSystemPack(updateExisting=true)` — templates are force-updated (`:150`).
- **Tenant scope**: bootstraps if missing (`:77-106`), **refuses to auto-apply when `conflicts_count>0`** (`manual_review_required`, `:108-113`), otherwise merges missing requirements (`:115`).

### 3.3 The tamper-evident audit — `compliance_framework_pack_events`
Model `app/Models/ComplianceFrameworkPackEvent.php`; table created by `database/migrations/2026_05_10_120000_create_compliance_framework_pack_events.php`. Properties:
- **Append-only / immutable**: `save()` throws if the row already exists (`:59-66`); `delete()` always throws (`:68-71`).
- `record()` (`:93-133`) writes: scope (`system|tenant`), event type (`system_sync|tenant_sync|tenant_bootstrap|tenant_purge`, constants `:14-20`), `pack_key`, `pack_version`, **`pack_checksum`** (`:122`), actor, `diff_before`/`diff_after`, `result_summary`, ip/user-agent, and a **hash chain**.
- **Checksum**: `checksumForPack()` = SHA-256 over a canonicalised (recursively `ksort`ed, bools→0/1, dates→ATOM) JSON of the whole pack (`:135-138`, `:172-192`). This is the pack's content fingerprint shown in the dashboard.
- **Hash chain** (`hashes()`, `:140-170`): `payload_hash = sha256(canonical(eventData))`; `event_hash = sha256({algorithm, payload_hash, previous_hash})`; `previous_hash` is the last event's `event_hash` **within the same (scope, pack_key, tenant)** partition, read `lockForUpdate()` (`:142-155`). Any silent edit/reorder breaks the chain.

Every audited surface calls `record()` after acting: system apply (`SyncComplianceFrameworkPacks.php:153`, `ComplianceFrameworkPacksController.php:72`), tenant bootstrap/sync (`ComplianceFrameworkPackTenantUpdater.php:108`, `SyncComplianceFrameworkPacks.php:88`/`:116`), purge (`ComplianceFrameworkPackPurger.php:111`). **The `snipeit:install-compliance-frameworks` command does NOT record events** — its `installCompanyPack`/`installSystemPack` calls are silent. If auditability matters, prefer the dashboard/`sync` command over the raw install command.

### 3.4 Purge (safe rollback of an unused bootstrap copy)
`ComplianceFrameworkPackPurger.php`. `purgeTenantPack()` (`:70-130`) hard-deletes a tenant pack framework **only if** `purgeBlockers()` (`:48-68`) is empty — it refuses when the framework is a system template, has a NULL/unknown `source_pack_key`, or has **any linked documents** (direct `documents.document_framework_id` or via the requirement↔document pivot, `:59-65`). Records `EVENT_TENANT_PURGE`. Dashboard route `purge_unused_bootstrap` requires a confirm checkbox (`ComplianceFrameworkPacksController.php:139-141`). This is why the auto-bootstrap cleanup migrations (`2026_05_19…`, `2026_05_20_085733_purge_unused_bootstrap_tenant_frameworks.php`) could remove empty bootstrap copies but left CodyCloud's evidence-bearing Allegato 2 (id 4) and Italway's Allegato 1 intact.

---

## 4. Precise, safe steps to apply packs

### 4.1 The jurisdiction gate you must understand first
`availablePackKeys($locale, $jurisdiction)` (`ComplianceFrameworkInstaller.php:17-56`) filters packs by locale, then groups by `bootstrap_group`/`compliance_domain` and keeps, per group, the pack with the best `jurisdictionPriority()` **< 2** (`:41-55`). `jurisdictionPriority()` (`:414-428`) returns:
- `0` — `source_register.scope == national_overlay` **and** jurisdiction ≠ EU **and** pack jurisdiction contains it (→ the two Allegato packs, only when the tenant jurisdiction is **IT**);
- `1` — `scope == eu_baseline` and pack jurisdiction contains EU (→ `gdpr_eu`, `ai_act_it`);
- `2` — everything else, i.e. **excluded**.

Consequences (verified against live tenants — CodyCloud=EU, Italway=IT, Suez group=EU):

| Tenant jurisdiction | Packs the tenant/dashboard path will offer |
|---|---|
| **EU** (CodyCloud t1, Suez t7) | `gdpr_eu`, `ai_act_it` **only** |
| **IT** (Italway t6) | `nis2_it_allegato_1`, `nis2_it_allegato_2`, `gdpr_eu`, `ai_act_it` |

**`dl81_it`, `iso27001_it`, `iso9001_it` are never reachable through the tenant/dashboard path** for any tenant (their `source_register.scope` is `national_baseline`/`international_standard` → priority 2 for both EU and IT). They can only be installed as system templates (no tenant) or via `--company_id` (§4.4). This is by design but non-obvious.

### 4.2 Preconditions (already satisfied in prod)
All 7 system templates exist (ids 106–112), so any operational install will correctly set `source_framework_id`. Installing a pack also auto-creates the global document types it references (`ComplianceFrameworkInstaller.php:357-389`) — expected side effect, not an error.

### 4.3 Apply **GDPR** and **NIS2 Allegato 1** to CodyCloud (company 1, tenant 1, jurisdiction EU)

**GDPR — clean, audited, via dashboard (recommended).** `gdpr_eu` is EU-scope, so it is available to CodyCloud.
1. Dashboard → pack `gdpr_eu` → *Apply to tenant* CodyCloud (`ComplianceFrameworkPacksController::applyTenant`, `:90`). This bootstraps a new operational GDPR framework on company 1 (`source_pack_key='gdpr_eu'`, `source_framework_id=111`) and records `EVENT_TENANT_BOOTSTRAP`.
   - CLI-equivalent dry-run first: `php artisan snipeit:sync-compliance-framework-packs gdpr_eu --tenant_id=1` (diff only), then add `--apply`.

**NIS2 Allegato 1 — blocked by the EU jurisdiction gate; two safe options:**

- **Option A (keeps the audit trail).** Set CodyCloud's tenant `default_compliance_jurisdiction` to **IT** (tenant config UI → `TenantsController` writes `tenants.default_compliance_jurisdiction`; normalized at `Tenant.php:293-298`). Then dashboard → `nis2_it_allegato_1` → *Apply to tenant* CodyCloud. Records `EVENT_TENANT_BOOTSTRAP`.
  - ⚠️ **Do not use bulk/"apply all"** while jurisdiction=IT: `nis2_it_allegato_2` also becomes available, and because CodyCloud's existing Allegato 2 (id 4) has slug `nis2` + NULL `source_pack_key`, the pack matches nothing and would create a **duplicate** Allegato 2. Apply `nis2_it_allegato_1` **only**.

- **Option B (surgical, no jurisdiction change; no audit event).**
  ```
  php artisan snipeit:install-compliance-frameworks nis2_it_allegato_1 --company_id=1 --visibility=private --dry-run
  php artisan snipeit:install-compliance-frameworks nis2_it_allegato_1 --company_id=1 --visibility=private
  ```
  Installs onto company 1 via `installCompanyPack` (`:70-76`, `:93-109`), bypassing the jurisdiction gate, `source_framework_id=109`. It touches nothing else (matches by `source_pack_key`/slug `nis2-it-allegato-1`, which CodyCloud lacks → fresh insert). Trade-off: this path writes **no** `compliance_framework_pack_event`.

Either way, **always dry-run first**, and never pass `--update-existing` unless you intend to overwrite.

### 4.4 Apply any pack to the other client companies (Suez / Suez Italy / Ecosistem / Logica / iblue / Econet / Deca)

These are **all one tenant** (tenant 7, jurisdiction EU) with a company sub-tree — verified:
```
Suez International 170 (root, parent NULL)
├─ Suez Italy 171
└─ Ecosistem 172
   ├─ Logica 2.0 173   ├─ iblue 174   ├─ Econet 175   └─ Deca Srl 176
```

Key constraint: the **tenant/dashboard path installs onto the tenant ROOT company only** — `bootstrapTenant`→`rootCompany()` and `tenantFramework`→`rootCompany()` (root here = Suez International 170). It **cannot** target Econet/iblue/Logica/Deca/Ecosistem/Suez-Italy individually, and (EU jurisdiction) would only offer `gdpr_eu`/`ai_act_it` even for the root.

**Therefore, to place any pack on a specific sub-company, use the company-scoped install command** (bypasses tenant + jurisdiction filters):
```
# dry-run, then apply — repeat per target company id
php artisan snipeit:install-compliance-frameworks <pack_key> --company_id=<id> --visibility=private --dry-run
php artisan snipeit:install-compliance-frameworks <pack_key> --company_id=<id> --visibility=private
```
- `<pack_key>` ∈ {`nis2_it_allegato_1`, `nis2_it_allegato_2`, `dl81_it`, `iso27001_it`, `iso9001_it`, `gdpr_eu`, `ai_act_it`}.
- `<id>` ∈ {170,171,172,173,174,175,176}.
- `--visibility`: `private` = visible only to that company; `descendants` = that company **and its children** (`Company::normalizeTemplateOwnership`, invoked at `InstallComplianceFrameworks.php:70-73`). For a leaf (e.g. Econet 175) use `private`; for Ecosistem 172 (has children) `descendants` would cover 173–176. **Never `global`** for a tenant company — `installCompanyPack` throws (`ComplianceFrameworkInstaller.php:147`).
- Each run: creates one operational framework + its requirements, links `source_framework_id` to the template, seeds any missing global document types. Idempotent by `(source_pack_key|slug, company_id)` — re-running without `--update-existing` skips.
- Audit caveat (§3.3): this command records **no** pack event. If the CodyCloud team needs the tamper-evident trail for these installs, the alternative is to (a) set tenant 7's jurisdiction appropriately and only for packs the gate allows (`gdpr_eu`/`ai_act_it`, root company only) drive them through the dashboard, or (b) accept that NIS2/ISO/DL81 on sub-companies are installed via the silent command and log the action out-of-band.

### 4.5 Verification after any install
```
# diff a tenant pack against the catalog (read-only)
php artisan snipeit:sync-compliance-framework-packs <pack_key> --tenant_id=<tenant>
# or inspect directly
document_frameworks WHERE company_id=<id> AND source_pack_key=<pack_key>  → expect source_pack_version=2026.05.21.1 (ai_act_it: 2026.05.14.1)
```
Confirm `requirements` count matches the table in §1, and that a `compliance_framework_pack_events` row was appended (only for dashboard/sync/updater paths).

---

### Key files (all under `app/` unless noted)
- Catalog/build: `Support/Compliance/ComplianceFrameworkPackCatalog.php` (+ `Support/Compliance/Packs/nis2_it_allegato_1.php`, `nis2_it_allegato_2.php`); exposed by `config/compliance_frameworks.php`.
- Install engine: `Support/Compliance/ComplianceFrameworkInstaller.php`.
- Diff/merge: `Support/Compliance/ComplianceFrameworkPackSync.php`.
- Audited tenant apply: `Support/Compliance/ComplianceFrameworkPackTenantUpdater.php`.
- Purge: `Support/Compliance/ComplianceFrameworkPackPurger.php`; Dashboard model: `Support/Compliance/ComplianceFrameworkPackDashboard.php`.
- Commands: `Console/Commands/InstallComplianceFrameworks.php`, `Console/Commands/SyncComplianceFrameworkPacks.php`.
- UI: `Http/Controllers/ComplianceFrameworkPacksController.php`; routes `routes/web.php:284-309`; auto-bootstrap `Http/Controllers/TenantsController.php:159-164,772-775`.
- Audit: `Models/ComplianceFrameworkPackEvent.php`; migration `database/migrations/2026_05_10_120000_create_compliance_framework_pack_events.php`.
- Provenance columns: `database/migrations/2026_05_07_130000_add_tenant_locale_and_framework_bootstrap_metadata.php`, `2026_05_08_113000_add_compliance_pack_version_to_document_frameworks.php`.
