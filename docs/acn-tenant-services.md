# ACN tenant services inventory

This feature adds a tenant-level services inventory for the annual ACN NIS categorisation workflow.

Official ACN guidance for 2026 requires essential and important entities to communicate or update the list of activities and services from 1 May to 30 June through the NIS platform. The export follows the categorised list structure used by the ACN workbook:

- `Macro-area`
- `Denominazione Attività/Servizio`
- `Descrizione`
- `Categoria di rilevanza pre-assegnata`
- `Categoria di rilevanza attribuita`

Tenant services are managed from the left navigation menu under `Servizi`, next to the operational registries such as suppliers, customers, and contracts. Services are tenant-scoped and can be linked to documents and customer contracts that belong to companies in the same tenant. Cross-tenant links are rejected during validation.

The `Produzione di beni e servizi` macro-area is split into the DNISA/ACN sector variants used by the ACN workbook, such as `Produzione di beni e servizi - Infrastrutture digitali`, `Produzione di beni e servizi - Gestione dei servizi TIC`, and `Produzione di beni e servizi - Fornitori di servizi digitali`. The previous generic value remains valid for already saved records, but new UI selections should use the specific DNISA/ACN variant.

Each service can also store a local `Base ACN/DNISA` note. Use it to record the declaration or category that makes the entity subject to NIS for that service. This note is intentionally not exported as a workbook column; the ACN export keeps the official five-column format. The supporting ACN/DNISA declaration, governance documents, and delivery or support contracts should be linked through the document and contract forms.

The ACN XLSX file is generated from controlled application data. The sample workbook in `tmp/` is intentionally not committed because it contains private subject data in the filename and workbook content.

References:

- https://www.acn.gov.it/portale/nis/categorizzazione
- https://www.acn.gov.it/portale/documents/d/guest/allegato_1_modello260409-v1
- https://www.acn.gov.it/portale/documents/d/guest/allegato_2_modello260409-v1
