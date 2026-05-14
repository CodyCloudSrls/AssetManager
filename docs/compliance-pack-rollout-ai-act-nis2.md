# AI Act And NIS2 Pack Rollout

Last updated: 2026-05-14

This rollout plan is the controlled release path for the AI Act and NIS2 compliance pack expansion. It assumes system packs are bootstrap sources and tenant copies are operational tenant data.

## Release Scope

Included:

- AI Act EU-baseline packs for every supported locale.
- NIS2 EU-baseline packs for every supported locale.
- `nis2_it` as the only national overlay.
- Source register metadata, pack checksums and immutable pack events.
- Superadmin-only pack console operations.

Excluded:

- No automatic tenant mass update.
- No non-Italian national overlay.
- No claim that generated pack content is legal advice.
- No destructive rewrite of tenant-edited frameworks or requirements.

## Pre-Rollout Checks

1. Confirm the source register:
   - `docs/compliance-source-register.md`
   - `config/compliance_frameworks.php`
2. Confirm NIS2 audit:
   - `docs/nis2-pack-audit.md`
3. Confirm no non-Italian NIS2 pack uses ACN or Italian national references.
4. Run available validation:
   - `php -l app/Support/Compliance/ComplianceFrameworkPackCatalog.php`
   - `php -r "include 'config/compliance_frameworks.php';"`
   - `php artisan snipeit:sync-compliance-framework-packs all --no-ansi`
   - `php artisan package:discover --no-ansi`
   - `php artisan view:cache --no-ansi`
   - `php artisan view:clear --no-ansi`
   - `git diff --check`
5. Run unit pack tests.
6. Run feature/integration tests only after a safe `.env.testing` is present.

## Tenant Selection

Use selected tenants only. A tenant is eligible when all of these are true:

- A platform superadmin has identified the tenant and expected compliance jurisdiction.
- The tenant root company exists and tenant default locale is correct.
- Existing NIS2/GDPR frameworks were checked in the pack console for conflicts.
- The responsible consultant accepts that AI Act and non-Italian NIS2 packs are baseline scaffolds.
- There is a rollback/hold plan for manual review findings.

Recommended first wave:

- One internal/demo tenant.
- One Italian tenant using `it-IT` plus `IT` compliance jurisdiction, expecting `nis2_it`.
- One non-Italian EU tenant using its locale plus national jurisdiction, expecting fallback to `nis2_eu`.
- One `en-US` tenant, expecting `nis2_en` and `ai_act_en`.

## Rollout Sequence

1. Deploy code and configuration.
2. Open `/admin/compliance-framework-packs` as platform superadmin.
3. Filter `domain = ai_act`; inspect every locale row.
4. Filter `domain = nis2`; confirm:
   - `nis2_it` shows national overlay.
   - every other NIS2 pack shows EU baseline.
5. Update system templates pack by pack.
6. Record event hashes for each system update.
7. For each selected tenant, open pack detail and inspect tenant diff.
8. Apply tenant-safe update only when the diff is actionable and has no conflicts; for multiple tenants, use only the selected-row bulk safe update on the pack detail page.
9. Record tenant event hashes.
10. Hand off any modified tenant copy to consultant review; do not overwrite it.

Tenant managers may also run the tenant-scoped safe update from tenant settings. That workflow uses the same non-destructive engine and cannot update tenant-modified requirements.

## Rollback And Hold

Code rollback:

- Reverts source pack definitions for future operations only.
- Does not delete tenant-created framework records.
- Does not remove append-only pack events.

Tenant hold:

- If a tenant diff shows changed existing fields, stop automatic apply.
- Export or review the diff manually with the consultant.
- Add missing requirements manually only if the consultant approves.

Source hold:

- If an official source changes materially, stop rollout for affected packs.
- Update `docs/compliance-source-register.md`.
- Re-run NIS2 or AI Act pack audit.
- Bump pack version only after the source/content decision is recorded.

## Consultant Operating Notes

- EU-baseline packs are structured starting points for evidence collection.
- Country overlays require official source validation and expert review.
- AI Act role, scope, high-risk, GPAI and prohibited-practice decisions remain consultant/client accountability.
- NIS2 supplier, asset, incident and continuity evidence must be reviewed, approved and, where appropriate, signed.
- Evidence integrity relies on document versions, checksums, assignment/event history and pack event hashes. Missing approval evidence is a real audit gap even when a requirement row exists.

## Completion Criteria

The WBS is complete when:

- Source register is documented and tested.
- `docs/nis2-pack-audit.md` exists and covers every NIS2 pack key.
- Rollout is controlled by this document and no mass tenant update is introduced.
- Consultant notes are linked from the framework documentation.
- Feature/integration tests remain explicitly blocked until `.env.testing` is provided, while unit/config checks pass.
- Non-Italian NIS2 jurisdictions remain `review_required` until a country-specific review is recorded.
