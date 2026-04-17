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

## Security

Report security issues privately using the policy in [SECURITY.md](SECURITY.md).

## Contributing

Contribution expectations are in [CONTRIBUTING.md](CONTRIBUTING.md). This fork is maintained with a production-first mindset, so operationally risky or branding-regressive changes may be declined even if they fit upstream conventions.

## Upstream Attribution

This repository is a forked derivative of Snipe-IT. Upstream copyright notices and license terms remain applicable under the AGPL.
