# CodyCloud Asset Management

CodyCloud Asset Management is the CodyCloud-maintained asset management platform used at `asset.codycloud.it`.

This codebase started from the Snipe-IT project and remains licensed under `AGPL-3.0-or-later`. CodyCloud-specific changes in this fork focus on:

- production operations and hardening
- CodyCloud default branding
- compliance-oriented customization for GDPR and NIS2 use cases
- fork hygiene for self-hosted and managed deployments

## Scope

This repository intentionally excludes live runtime material such as:

- `.env` and host-specific secrets
- uploaded media and tenant data
- local backups, dumps, and operational artifacts

Those assets stay outside Git and are managed per environment.

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

## Security

Report security issues privately using the policy in [SECURITY.md](SECURITY.md).

## Contributing

Contribution expectations are in [CONTRIBUTING.md](CONTRIBUTING.md). This fork is maintained with a production-first mindset, so operationally risky or branding-regressive changes may be declined even if they fit upstream conventions.

## Upstream Attribution

This repository is a forked derivative of Snipe-IT. Upstream copyright notices and license terms remain applicable under the AGPL.
