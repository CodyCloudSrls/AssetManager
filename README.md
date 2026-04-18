# CodyCloud Asset Management

CodyCloud Asset Management is the CodyCloud-maintained asset management platform used at `asset.codycloud.it`.

This codebase started from the Snipe-IT project and remains licensed under `AGPL-3.0-or-later`. CodyCloud-specific changes in this fork focus on:

- production operations and hardening
- CodyCloud default branding
- a dedicated document registry for compliance and governance workflows
- compliance-oriented customization for GDPR, NIS2, AI Act, and safety use cases
- fork hygiene for self-hosted and managed deployments

## Scope

This repository intentionally excludes live runtime material such as:

- `.env` and host-specific secrets
- uploaded media and tenant data
- local backups, dumps, and operational artifacts

Those assets stay outside Git and are managed per environment.

This repository also intentionally excludes legacy one-shot installer and upgrade helpers that targeted upstream deployment paths and remotes. The supported maintenance workflows are the repository-local Laravel and container workflows described here.

## Local Development

Basic Laravel workflow:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan passport:install
npm run dev
php artisan serve
```

Adjust storage, mail, queue, and database settings for your environment before use.

## Documents Module

The fork includes a first-class `documents` module with dedicated data tables, UI, uploads, notes, and history.

Document governance settings are managed through:

- `Settings > Document Types`
- `Settings > Document Frameworks`

The reference taxonomy and implementation notes for the compliance-oriented registry live in [docs/document-registry-frameworks.md](docs/document-registry-frameworks.md).

## Tickets And Helpdesk

The fork also includes a first-class `tickets` module for internal support operations and tenant-facing public helpdesk intake.

Implemented capabilities include:

- internal ticket creation and lifecycle management
- operational workflow updates with separated `operate` vs `edit` permissions
- worklogs and time tracking
- uploads, notes, and history
- links to assets, users, locations, companies, and documents
- tenant-aware queue filtering
- public guest ticket portal per tenant
- tenant-specific public helpdesk settings and public URL slug

Operational notes and the public helpdesk model are documented in [docs/helpdesk-ticketing.md](docs/helpdesk-ticketing.md).

## Multi-Tenant Model

The fork now runs in always-on multi-tenant mode. The legacy runtime flags for optional multi-company scoping are no longer part of normal operations.

- `Tenant` is the SaaS boundary and is identified by a UUID
- each tenant is anchored to a root company; the root company provides the tenant-facing display name and branding
- operational records remain company-scoped
- company hierarchy is supported through parent-child relationships
- parent tenants can see descendant operational data in their subtree
- the top-bar switcher operates on tenant context, not raw company context
- the switcher exposes a global `All` context for aggregated views across authorized tenants
- superadmins can stay in global platform context or switch into a tenant context
- explicit tenant memberships support `tenant admin` and `tenant viewer` roles for cross-tenant users
- tenant admins can manage their own tenant settings and cross-tenant memberships without gaining platform superadmin access
- tenant admins and viewers do not see platform superadmins in tenant-scoped user management flows
- tenant public helpdesk settings are inherited from the tenant root company
- public helpdesk URLs can use a tenant-specific slug while keeping UUID fallback compatibility
- shared settings templates can be marked as `private`, `descendants`, or `global`
- categories, status labels, manufacturers, suppliers, depreciations, document types, document frameworks, fieldsets, and asset models follow tenant-aware ownership and visibility rules
- locations are always tenant-scoped and do not support global sharing

The production baseline on `asset.codycloud.it` keeps the existing CodyCloud catalog private to CodyCloud and exposes only a minimal global starter set for new tenants.

## Default Permission Groups

The repository seeds production-oriented baseline groups that can be reused or adapted per tenant:

- `Default - Helpdesk Operator`
- `Default - Asset Manager`
- `Default - Compliance Manager`
- `Default - Read Only Auditor`

## Security

Report security issues privately using the policy in [SECURITY.md](SECURITY.md).

## Contributing

Contribution expectations are in [CONTRIBUTING.md](CONTRIBUTING.md). This fork is maintained with a production-first mindset, so operationally risky or branding-regressive changes may be declined even if they fit upstream conventions.

## Upstream Attribution

This repository is a forked derivative of Snipe-IT. Upstream copyright notices and license terms remain applicable under the AGPL.
