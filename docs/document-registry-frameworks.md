# Document Registry Blueprint

This instance now includes a first-class `documents` module.

The registry is no longer implemented as asset category/model/custom fields. Documents now live in dedicated tables and UI screens, with their own lifecycle, uploads, notes and history.

The controlled taxonomies for the module are now first-class settings resources:

- `Settings > Document Types`
- `Settings > Document Frameworks`

These settings have dedicated CRUD screens, API endpoints, select lists for the document form, soft delete support, and usage-aware delete protection when documents are still assigned.

Core reference tables:

- `documents`
- `document_assignments`
- `document_types`
- `document_frameworks`
- `document_framework_requirements`
- `document_framework_requirement_document`

## Framework Governance Model

Frameworks are now production-governed objects, not simple labels.

Each framework can carry:

- authority / issuing body
- framework code
- framework type
- jurisdiction
- version
- valid-from and valid-to dates
- owner
- review cadence
- status (`draft`, `active`, `superseded`, `archived`)
- external reference URL

This allows one tenant to maintain both external obligations and internal governance frameworks in the same catalog without collapsing everything into free-text metadata.

## Framework Requirements

Each framework can now define an explicit requirement library.

Typical metadata per requirement:

- requirement code
- title
- domain
- parent requirement
- owner
- preferred document type
- review cadence
- evidence guidance
- applicability notes
- active / mandatory flags

This is the minimum structure needed to answer real production questions such as:

- which obligations are still uncovered
- which obligations are covered only by supporting evidence
- which obligations are at risk because their primary evidence is obsolete or overdue
- which obligations are fully covered by active governed documents

## Coverage Mapping

Documents can now map to one or many framework requirements with explicit coverage roles:

- `primary`
- `supporting`

Coverage is derived from these mappings rather than typed manually.

Current coverage states are:

- `missing`
- `supporting_only`
- `at_risk`
- `covered`

## Core Metadata

Each document record should at least capture:

- document type
- framework family
- regulatory or control reference
- owner function
- document status
- version
- issue date
- effective date
- next review date
- internal document ID
- confidentiality classification
- retention rule
- scope
- evidence link

## Suggested Document Types

Use `Document Type` to normalize the registry across frameworks.

Current seeded defaults in this fork are:

- Policy
- Procedura
- Registro
- Valutazione
- Piano
- Informativa
- Nomina
- Verbale
- Evidenza
- Inventario

Additional practical candidates, depending on operating model:

- Policy
- Procedura
- Istruzione operativa
- Registro
- Valutazione / analisi
- Piano
- Nomina / incarico
- Verbale / report
- Modulo / template
- Evidenza / attestazione
- Contratto / accordo
- Standard / linea guida

## Suggested Framework Families

Use `Document Framework` to classify the main obligation family.

Current seeded defaults in this fork are:

- Generale
- Dlgs 81/2008
- GDPR
- NIS2
- AI Act

Additional practical candidates, depending on governance scope:

- Privacy nazionale
- Cybersecurity
- ISO 27001 / 27002
- ISO 22301
- Multi-framework
- Altro

## Compliance Pack Source Control

Generated compliance framework packs are controlled through a source register and a conservative rollout model:

- `docs/compliance-source-register.md` records official source URLs, jurisdiction, source status, review date and impacted pack keys.
- `docs/nis2-pack-audit.md` records the current NIS2 pack audit and confirms that only `nis2_it` is a national overlay.
- `docs/compliance-pack-rollout-ai-act-nis2.md` defines the controlled AI Act/NIS2 rollout path for selected tenants.

Consultant note: EU-baseline packs are evidence scaffolds. Country overlays require official source validation and expert review. Final applicability, evidence approval, signatures and client advice remain consultant/client responsibilities.

## Working Taxonomy By Framework

This is a practical baseline, not a legal completeness claim.

### Dlgs 81/2008

Typical document families:

- DVR
- DUVRI, where applicable
- piani di emergenza
- nomine e incarichi (for example RSPP, addetti emergenza, medico competente where applicable)
- registri formativi e attestazioni
- verbali, controlli e manutenzioni rilevanti per salute e sicurezza
- procedure operative e istruzioni di sicurezza

Official references used for the structure:

- Dlgs 9 aprile 2008, n. 81: https://www.gazzettaufficiale.it/eli/id/2008/04/30/008G0104/s
- INAIL, valutare il rischio / DVR: https://www.inail.it/portale/prevenzione-e-sicurezza/it/come-fare-per/valutare-il-rischio.html

### GDPR

Typical document families:

- registro delle attivita di trattamento
- informative privacy
- procedura data breach
- procedura gestione diritti degli interessati
- policy di conservazione
- DPIA, where applicable
- nomine e accordi con responsabili del trattamento
- valutazioni su trasferimenti, where applicable

Official references used for the structure:

- Regulation (EU) 2016/679: https://eur-lex.europa.eu/eli/reg/2016/679/oj/eng
- Garante, istruzioni sul registro dei trattamenti: https://www.garanteprivacy.it/home/docweb/-/docweb-display/docweb/9047529

### NIS2

For Italy, the main current legal anchor is Dlgs 4 settembre 2024, n. 138.

Pack policy:

- `nis2_it` is the only shipped national overlay.
- Other NIS2 locale packs remain EU baseline and require national review before country-specific use.
- Supplier evidence should include relevance rationale, criticality, CPV, contracts/SLA/DPA, contact/accountability data and review status where applicable.

Typical document families:

- perimetro soggetti e servizi rilevanti
- analisi dei rischi
- incident management and reporting procedure
- business continuity, backup, disaster recovery and crisis management
- supply-chain security procedure
- vulnerability handling and patching procedure
- access control and identity management procedure
- monitoring, logging and detection evidence
- awareness and training records
- asset and dependency inventory evidence

Official references used for the structure:

- Directive (EU) 2022/2555: https://eur-lex.europa.eu/eli/dir/2022/2555/oj/eng
- Italy, Dlgs 4 settembre 2024, n. 138: https://www.gazzettaufficiale.it/eli/id/2024/10/01/24G00155/SG

### AI Act

Typical document families:

- AI system inventory
- use-case classification and applicability assessment
- prohibited-practice assessment
- risk-management documentation
- human oversight procedure
- transparency and user-information artefacts
- data governance documentation
- post-market monitoring and incident logging, where applicable
- technical documentation for high-risk systems, where applicable

Official references used for the structure:

- Regulation (EU) 2024/1689: https://eur-lex.europa.eu/eli/reg/2024/1689/oj
- Annex IV technical documentation reference: https://eur-lex.europa.eu/legal-content/EN/TXT/?qid=1736344989138&uri=CELEX%3A32024R1689

Pack policy:

- AI Act packs are EU baseline packs for every supported locale.
- The pack structures AI inventory, operator-role assessment, prohibited-practice screening, high-risk/GPAI evidence and integrity records.
- Commission AI Act implementation dates and simplification updates must be rechecked before client-specific advice.

## Operating Recommendation

Use one document record per governed document, not one record per framework.

Recommended naming convention:

- document name: human title of the document
- document number: controlled internal code
- type: controlled document family
- framework: main legal or governance family
- metadata fields: owner, status, classification, retention, dates and evidence links

This keeps the registry usable both for inventory and for audit evidence collection without overloading the asset domain.

## Assignment Model

Production document registers also need an explicit applicability layer.

This fork now supports document assignments to:

- users, for example attestati, abilitazioni, lettere di incarico, acknowledgements, certifications
- assets, for example maintenance certificates, declarations, conformity evidence, test records
- locations, for example site procedures, evacuation plans, permits, inspection evidence

Assignments are not implemented as checkout/checkin flows. They are persistent relationships with their own metadata:

- relationship type, such as `issued_to`, `applies_to`, `required_for`, `evidence_for`
- operational status, such as `planned`, `required`, `active`, `completed`, `expired`, `revoked`
- issuer
- assignment reference
- issue, effective, expiry, renewal, completion, and revocation dates
- notes

This is the production baseline needed to manage real certification and compliance evidence without polluting the asset lifecycle model.

## Implementation Status

At the time of this document, the repository includes:

- dedicated web and API CRUD for documents
- dedicated web and API CRUD for document types and frameworks
- dedicated web and API CRUD for framework requirements
- sidebar filters for document status and review windows
- quick-create modals for document types and frameworks from the document form
- requirement-to-document mapping directly from the document form
- document-to-user, document-to-asset, and document-to-location assignments with dedicated lifecycle metadata
- framework coverage summaries and requirement views
- framework requirement matrix views with coverage, owner, risk/review state and linked evidence
- AI Act and NIS2 pack source register, NIS2 pack audit and controlled rollout documentation
- locale coverage seeded across the available language folders so the module does not fall back to missing keys
