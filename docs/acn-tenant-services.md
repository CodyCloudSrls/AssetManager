# ACN tenant services inventory

This feature adds a tenant-level services inventory for the annual ACN NIS categorisation workflow.

Official ACN guidance for 2026 requires essential and important entities to communicate or update the list of activities and services from 1 May to 30 June through the NIS platform. The export follows the categorised list structure used by the ACN workbook:

- `Macro-area`
- `Denominazione Attività/Servizio`
- `Descrizione`
- `Categoria di rilevanza pre-assegnata`
- `Categoria di rilevanza attribuita`

Tenant services are managed from the tenant admin area under `Servizi tenant`. Services are tenant-scoped and can be linked to documents and customer contracts that belong to companies in the same tenant. Cross-tenant links are rejected during validation.

The ACN XLSX file is generated from controlled application data. The sample workbook in `tmp/` is intentionally not committed because it contains private subject data in the filename and workbook content.

References:

- https://www.acn.gov.it/portale/nis/categorizzazione
- https://www.acn.gov.it/portale/documents/d/guest/allegato_1_modello260409-v1
- https://www.acn.gov.it/portale/documents/d/guest/allegato_2_modello260409-v1
