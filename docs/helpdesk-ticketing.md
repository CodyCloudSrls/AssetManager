# Helpdesk And Ticketing

This fork includes a tenant-aware `tickets` module and a tenant-specific public helpdesk portal.

The feature is intended to cover:

- internal support workflows
- public guest ticket intake for non-registered users
- operational ticket updates and time tracking
- linkage to assets, users, documents, companies and locations

## Core Scope

The module includes:

- dedicated tables for `tickets`, `ticket_statuses`, `ticket_priorities`, `ticket_types`, and `ticket_worklogs`
- internal web and API CRUD for tickets
- internal comments, uploads, history and worklogs
- tenant-aware queue filtering
- public guest portal per tenant
- tenant-specific public helpdesk settings

## Internal Workflow

The internal operator workflow is split into two permission levels:

- `tickets.operate`
  use for day-to-day support work: notes, worklogs, status, priority, type, assignee and SLA changes
- `tickets.edit`
  use for full post-creation ticket editing

This keeps the operational flow available to support staff without granting unrestricted structural edits.

## Ticket Queues

The ticket index supports production queues for:

- all
- open
- mine
- unassigned
- waiting customer
- waiting vendor
- public portal
- SLA at risk
- closed

## Public Helpdesk

Public guest intake is configured per tenant from:

- `Admin > Tenants > <Tenant> > Configure helpdesk`

Configurable settings:

- public portal enabled or disabled
- attachment support enabled or disabled
- public URL slug
- intro text
- privacy note
- helpdesk contact email
- helpdesk contact phone
- exposed public ticket types

Public URLs support:

- a tenant-specific slug, for example `/helpdesk/acme-inc`
- legacy UUID fallback, for example `/helpdesk/<tenant-uuid>`

This keeps old links working while allowing clean branded URLs.

## Public Ticket Types

The public form only exposes ticket types that are:

- marked `is_public = true`
- visible to the tenant under the tenant/template visibility rules
- explicitly selected in the tenant helpdesk settings

If no explicit selection exists, the tenant falls back to all visible public ticket types.

## Time Tracking

Worklogs support:

- minutes
- category
- billable flag
- started at
- ended at
- operator notes

Suggested operational categories already present in the module:

- analysis
- remote support
- on-site
- vendor coordination
- documentation
- administration

## Default Groups

The fork seeds a baseline set of production-ready permission groups:

- `Default - Helpdesk Operator`
- `Default - Asset Manager`
- `Default - Compliance Manager`
- `Default - Read Only Auditor`

These are intended as safe starting points, not hard constraints. Tenants can clone or adapt them to their own operating model.

## Notes For Operations

- public helpdesk pages are served with no-store/no-cache headers
- public attachments are blocked when disabled at tenant level
- public ticket access uses the ticket portal token
- ticket author display intentionally resolves across user scope for history integrity, while operational record access remains tenant-aware
