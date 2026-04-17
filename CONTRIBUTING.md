# Contributing

This fork is maintained for real production use, so contributions are reviewed against operational impact first.

## Baseline Expectations

- keep changes scoped and reversible
- include tests when behavior changes
- avoid introducing CodyCloud runtime secrets, media, dumps, or backups into Git
- preserve AGPL notices and upstream attribution where legally required
- prefer generic, reusable improvements over tenant-specific hacks

## Before Opening a PR

- run the relevant test suite
- document config or migration impact
- call out any UX, API, or data-model change clearly
- avoid reintroducing upstream branding, links, or metadata unless the change is explicitly about upstream attribution

## Security and Sensitive Reports

Do not use public issues for undisclosed vulnerabilities. Follow [SECURITY.md](SECURITY.md).
