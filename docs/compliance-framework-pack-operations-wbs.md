# Compliance Framework Pack Operations WBS

Last updated: 2026-05-14

Implementation status as of 2026-05-14: complete and closed. The original superadmin workflow remains the primary control surface; tenant manager self-service and bulk operations are now implemented only as non-destructive safe-update workflows with explicit user action, conflict blocking and immutable pack events.

## Goal

Expose daily superadmin operations for NIS2/GDPR framework packs without weakening tenant isolation. System packs remain bootstrap sources; tenants keep editable operational copies; updates are applied only through explicit non-destructive merge actions.

## Scope

- Show every configured compliance framework pack with locale, version, checksum and system-template status.
- Show compatible tenants for each pack based on tenant default bootstrap language.
- Compare system templates and tenant copies against the current configured pack.
- Apply system-template updates explicitly.
- Apply tenant updates only when the operation is non-destructive.
- Record successful write operations in an append-only hash-chained audit table.

## Guardrails

- No automatic overwrite of tenant-edited requirements.
- No background mass update without a visible operator action.
- No legal content authoring workflow beyond the existing `config/compliance_frameworks.php` source packs.
- No pack content may be presented as jurisdiction-specific advice unless the source register marks the pack as verified for that jurisdiction and a consultant completes client applicability review.

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
- Tenant managers can trigger the same safe update from tenant settings for their own tenant only.
- Platform superadmins can apply a pack to selected compatible tenants in bulk only by selecting tenant rows explicitly.

Verification:

- Missing tenant framework can be bootstrapped from the pack.
- Missing tenant requirements are created.
- Existing tenant-modified requirement fields are not overwritten.
- Pack locale mismatch blocks the action.
- Bulk tenant update skips incompatible/current/manual-review tenants and records per-tenant outcomes.

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

For selected-tenant bulk updates:

1. Filter or open a single pack detail page.
2. Select only compatible tenant rows with actionable diffs.
3. Submit the selected-tenant safe update form.
4. Review the operation summary and immutable events.
5. Keep all manual-review rows out of automated application.

For tenant manager self-service:

1. Open tenant settings.
2. Select the compliance framework safe update option.
3. The update creates missing tenant frameworks, missing requirements or clean metadata only.
4. Any modified tenant copy remains blocked for manual review.

## Linked Source And Rollout Controls

- Source register: `docs/compliance-source-register.md`.
- NIS2 pack audit: `docs/nis2-pack-audit.md`.
- AI Act/NIS2 controlled rollout: `docs/compliance-pack-rollout-ai-act-nis2.md`.

Operational status as of 2026-05-21: NIS2 bootstrap ships only the Italian Allegato 1 and Allegato 2 packs with pack version `2026.05.21.1`. All non-Italian NIS2 jurisdictions remain `review_required` and require tenant manual curation until expert national review is recorded.

## Closure Notes

- Legal/expert review is not removed; it is enforced as an operating constraint through source-register status, EU-baseline/national-overlay labels and manual review notes.
- Tenant manager self-service is complete for non-destructive safe updates in the tenant settings workflow.
- Bulk tenant apply is complete only as selected-row superadmin safe update. No background all-tenant propagation exists.
- Pack operations remain complete only when tenant-edited conflicts are reviewed by a qualified consultant instead of overwritten.
