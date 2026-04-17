# Document Registry Blueprint

This instance now includes a document-registry foundation built around:

- asset category: `Documenti`
- asset model: `Documento normativo`
- fieldset: `Registro documenti normativi`

The goal is to inventory normative, procedural, governance and compliance documents in one place with a shared metadata model.

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

Use `Documento - Tipo` to normalize the registry across frameworks:

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

Use `Documento - Framework` to classify the main obligation family:

- Generale
- Dlgs 81/2008
- GDPR
- NIS2
- AI Act
- Privacy nazionale
- Cybersecurity
- ISO 27001 / 27002
- ISO 22301
- Multi-framework
- Altro

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

## Operating Recommendation

Use one asset record per governed document, not one record per framework.

Recommended naming convention:

- asset name: human title of the document
- asset tag or internal code: document control ID
- category: `Documenti`
- model: `Documento normativo`
- custom fields: governance metadata

This keeps the registry usable both for inventory and for audit evidence collection.
