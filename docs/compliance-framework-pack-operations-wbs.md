# Compliance Framework Pack Operations WBS

Last updated: 2026-05-10

## Goal

Expose daily superadmin operations for NIS2/GDPR framework packs without weakening tenant isolation. System packs remain bootstrap sources; tenants keep editable operational copies; updates are applied only through explicit non-destructive merge actions.

## Scope

- Show every configured compliance framework pack with locale, version, checksum and system-template status.
- Show compatible tenants for each pack based on tenant default bootstrap language.
- Compare system templates and tenant copies against the current configured pack.
- Apply system-template updates explicitly.
- Apply tenant updates only when the operation is non-destructive.
- Record successful write operations in an append-only hash-chained audit table.

## Out Of Scope

- No automatic overwrite of tenant-edited requirements.
- No background mass update without a visible operator action.
- No legal content authoring workflow beyond the existing `config/compliance_frameworks.php` source packs.
- No tenant manager self-service pack update yet; this first release is platform-superadmin only.

## Deliverables

### 1. Pack Operations Console

- Admin settings card: `Compliance Framework Packs`.
- Pack list with pack key, name, locale, domain, version, checksum, system status and tenant counters.
- Pack detail page with system diff, compatible tenant rows and recent audit events.

Verification:

- Platform superadmin without active tenant can open `/admin/compliance-framework-packs`.
- A normal tenant context cannot access the console.
- Every configured pack in `config/compliance_frameworks.php` appears exactly once.

### 2. Safe Update Actions

- `Update system template` applies the pack to the hidden system template.
- `Apply safe update` on a tenant creates missing framework copies or missing requirements only.
- Tenant records with changed existing fields are marked for manual review.

Verification:

- Missing tenant framework can be bootstrapped from the pack.
- Missing tenant requirements are created.
- Existing tenant-modified requirement fields are not overwritten.
- Pack locale mismatch blocks the action.

### 3. Immutable Audit Evidence

- New append-only `compliance_framework_pack_events` table.
- SHA-256 checksum of the pack payload is recorded.
- Payload hash and event hash are recorded.
- `previous_hash` chains events per scope and pack.
- Model updates/deletes are blocked after creation.

Verification:

- Applying a system or tenant update creates an audit event.
- `event_hash` changes when event payload changes.
- Updating or deleting an event through the model raises an exception.
- The UI shows recent audit event hashes.

## Operational Rule

The safe daily workflow is:

1. Update or review the code-level pack source.
2. Open the pack console.
3. Review system and tenant diff counts.
4. Update the system template.
5. Apply tenant-safe updates one tenant at a time.
6. Manually inspect modified tenant copies before making consultant-level changes.

## Residual Risks

- Pack content still requires expert legal review before being treated as jurisdiction-specific advice.
- Tenant manager self-service is intentionally deferred until the superadmin workflow has been validated.
- Bulk tenant apply is intentionally deferred to avoid accidental mass propagation.
