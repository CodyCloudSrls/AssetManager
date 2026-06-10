# CodyCloud Asset Fix WBS - 2026-06-09

Scope: follow-up fixes from the CodyCloud Asset fork analysis covering debug cleanup, ticket document visibility, dependency audit hardening, and security mitigation.

## WBS 1 - Remove Debug Mail Route

Status: completed.

Work done:

- Removed authenticated `/test-email` diagnostic route from `routes/web.php`.
- Removed the now-unused `CheckoutComponentMail` route import.

Verification:

- `php -l routes/web.php`
- `git diff --check`

## WBS 2 - Ticket Document Visibility Scope

Status: completed.

Work done:

- Applied compliance-domain and document-area visibility scopes to ticket document dropdowns.
- Applied the same visibility scopes when loading linked ticket documents in web ticket detail and API ticket responses.
- Hardened `StoreTicketRequest` so a ticket cannot be linked to a same-tenant document that the actor cannot view under document policies.

Verification:

- `tests/Feature/Tickets/Ui/TicketDocumentAccessScopeTest.php`
- Existing document access and NIS real coverage tests.

## WBS 3 - Composer Audit And Dependency Hygiene

Status: completed for patchable advisories; Laravel major upgrade remains separate.

Work done:

- Updated patchable vulnerable dependencies through Composer.
- Raised `symfony/css-selector` and `symfony/dom-crawler` dev constraints from Symfony 4.4 to 7.4.
- Normalized the `arietimmerman/laravel-scim-server` fork repository as a pinned Composer package definition because the VCS repository path was fragile during dependency resolution.
- Added temporary application mitigation for Laravel CVE-2026-48019 by rejecting CR/LF sequences in email-like request fields before validation reaches mail-sensitive data.

Remaining:

- `composer audit` still reports CVE-2026-48019 for `laravel/framework`.
- Composer reports the fixed ranges as Laravel `12.60.0+` or `13.10.0+`.
- Laravel 12 is currently blocked by package constraints, including the SCIM package, Larastan, Collision, and Spatie Backup. Treat this as a dedicated Laravel 12 upgrade WBS.

Verification:

- `composer validate --strict --no-interaction`
- `composer audit --no-interaction`
- `php artisan package:discover --no-ansi`
- `tests/Feature/Security/RejectEmailHeaderInjectionTest.php`

## WBS 4 - Regression Verification

Status: completed.

Commands run:

- `composer validate --strict --no-interaction`
- `php artisan package:discover --no-ansi`
- `php artisan view:cache --no-ansi && php artisan view:clear --no-ansi`
- `git diff --check`
- `php artisan test tests/Feature/Tickets/Ui/TicketDocumentAccessScopeTest.php tests/Feature/Security/RejectEmailHeaderInjectionTest.php tests/Feature/TenantServices/Ui/TenantServicesTest.php tests/Feature/Documents/Api/DocumentAccessScopeTest.php tests/Feature/Reporting/NisRiskMatrixReportTest.php tests/Unit/DefaultPermissionGroupsTest.php tests/Unit/DocumentFrameworkRequirementRiskTest.php --no-ansi`

Result:

- 43 tests passed, 312 assertions.
- Composer audit reduced from 11 advisories to 1 residual Laravel framework advisory.
