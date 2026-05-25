# Default Permission Groups

Last updated: 2026-05-25

This fork ships 14 production-oriented default permission groups. They are intended as conservative tenant baselines, not as a replacement for tenant-specific access reviews.

The canonical code definition is `App\Support\DefaultPermissionGroups`. The hardening migration `2026_05_25_191700_harden_default_permission_group_scopes.php` re-syncs system groups from that definition and intentionally has a no-op rollback so a deploy cannot automatically loosen access after hardening.

## Guardrails

- No default group receives `documents.force_delete`.
- System groups can be edited by platform superadmins through the normal group UI/API policy path.
- System groups remain protected from deletion.
- Document-area permissions are explicit for `administration`, `it`, and `cybersecurity`.
- Legacy documents without a `document_area` are visible only to users without area restrictions or to users whose group covers every document area for the relevant ability.
- Users limited to one document area do not gain visibility over unclassified legacy documents.
- Direct per-user permissions still override group baselines and must be reviewed separately during access audits.

## Group Scope Summary

| Group | Intended scope |
| --- | --- |
| `Default - Helpdesk Operator` | Ticket operation with read-only context for assets, documents, users, locations, and companies. |
| `Default - Service Desk Manager` | Full ticket lifecycle and support attachments, with read-only support context. |
| `Default - Inventory Operator` | Day-to-day inventory movement, assignment, audit, and checkout/checkin operations. |
| `Default - Asset Manager` | Asset and inventory-object administration, including catalog metadata, without platform superadmin rights. |
| `Default - Procurement And Catalog Manager` | Procurement and catalog governance for suppliers, manufacturers, models, categories, kits, licenses, and purchasing metadata. |
| `Default - Administration Document Updater` | Create and update administration-area documents and attachments only. |
| `Default - IT Document Updater` | Create and update IT-area documents and attachments only, with asset/user/location context. |
| `Default - Cybersecurity Document Updater` | Maintain cybersecurity evidence, inspect IT evidence, map requirements, and view NIS-specific reports. |
| `Default - Document Controller` | Manage controlled document registers, document types, frameworks, mappings, and related ticket workflows. |
| `Default - Compliance Evidence Coordinator` | Update evidence documents and mappings across all document areas while framework governance remains read-only. |
| `Default - Compliance Manager` | Manage document and compliance operations with asset and user data kept read-only. |
| `Default - Executive Read Only` | Leadership read-only profile for reports, tickets, document coverage, inventory summaries, and business context. |
| `Default - Read Only Auditor` | NIS/document audit read-only profile with no generic reports, ticket, customer, contract, or catalog administration access. |
| `Default - Tenant Operations Admin` | High-scope tenant operations administration without platform superuser powers. |

## Review Checklist

Before assigning or changing these groups in production:

- confirm whether the user needs operational writes, document writes, or read-only audit access;
- check direct user permissions as well as group membership;
- verify document-area coverage for users who need to see legacy unclassified documents;
- avoid granting broad tenant operations access for audit-only users;
- keep evidence deletion and force-delete powers outside the default baselines.
