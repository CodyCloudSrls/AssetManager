# ADR — Connettore FiC → Asset: architettura

- **Stato:** Accettato (2026-08-12)
- **Contesto:** riconciliare i documenti d'acquisto di Fatture in Cloud con gli asset/cespiti, unidirezionale (FiC = fonte). Esiste già un connettore FiC in-app (FicClient, FicRateGuard, FicSyncService, specchio `fic_documents`) schedulato.
- **Decisione:** estendere il connettore **in-app** con un comando schedulato `fic:reconcile` + `FicReconcileService`, NON un servizio middleware separato. Analisi prodotta con judge-panel multi-agente verificato sul codice.
- **Fase 0 (dry-run report) implementata:** comando `fic:reconcile` (sola lettura, 0 chiamate FiC) — vedi risultati reali in fondo.

---

# FiC → Asset Reconciliation — Architecture Recommendation

## 1. Verdict

**Build it in-app as a scheduled Artisan command (`fic:reconcile`) plus a `FicReconcileService`, cloning the proven `FicSyncDocuments` skeleton — assisted, not fully-automatic.** This is the only option that shares the single thing that actually constrains this system: the file-cache-backed `FicRateGuard` state that enforces the SHARED 1000/h + 40000/mo FiC quota. An in-process consumer reads and trips the *same* cooldown as `fic:sync`/`fic:sync-cassa`; a separate service (option B) or parallel queue workers (option C) each keep their own guard state and can silently burst the shared budget — the exact failure the quota constraint exists to prevent. In-app also gets transactional Asset-DB writes for free (custom-field write + `logUpload` + integrity metadata in one connection), reuses `FicClient`/`FicRateGuard`/`HasUploads`/custom-field columns 32–37 with essentially zero duplication, and adds **no new credential surface** — sidestepping the deferred, unscoped Passport service-token work entirely. Options B and C only pay off for throughput/latency/isolation problems this workload does not have: the match is pure indexed DB work over a tiny relevant set (8 matching VATs, 5 asset-owning suppliers). The one honest caveat that shapes the design: the data does **not** support automatic 1:1 invoice→asset linking, so the deliverable is a *matching + auto-link-the-unambiguous-cases* command **plus a human-confirmation surface**, never a "cron that solves reconciliation."

## 2. Comparison across the 9 criteria

| # | Criterion | A: In-app command | C: In-app queued | B: Separate service |
|---|-----------|:---:|:---:|:---:|
| 1 | Reuse / no-spaghetti | **5** — reuses client, guard, skeleton, uploads | 4 — adds a 2nd execution paradigm | 1 — must reimplement client + guard + schema |
| 2 | Two systems + middleware | **5** — reads FiC, writes only asset metadata | 5 — same | 5 — physically separate (its only real win) |
| 3 | SHARED quota safety | **5** — shares the one guard state | 2 — check-then-act races across workers | 2 — split guard state, uncoordinated |
| 4 | Idempotency / no-dupes | **4** — gate on marker; atomic write+attach | 3 — retries/re-dispatch enlarge dupe surface | 3 — dedupe over HTTP round-trips, racy |
| 5 | Prod / multi-tenant + transactional | **4** — one txn, needs company_id clamp | 3 — needs queue infra + per-job scope | 2 — non-atomic REST calls → half-linked assets |
| 6 | Security / token | **5** — no new creds | 5 — no new creds | 2 — forces deferred unscoped write token |
| 7 | Operational simplicity | **5** — one `->dailyAt()` line | 2 — worker daemon + failed_jobs | 2 — 2nd repo/deploy/cron/log/monitor |
| 8 | Testability | **4** — pure DB match, static-guard reset friction | 4 — clean dispatch seam | 3 — must test both sides + the wire |
| 9 | Effort / time-to-value | **4** — copy the skeleton | 2 — highest in-app effort | 1 — worst; new infra + verify Asset REST |
| | **Total** | **41 / 45** | **30 / 45** | **21 / 45** |

## 3. Recommended design (concrete)

**Where the code lives**
- `app/Console/Commands/FicReconcileAssets.php` — signature `fic:reconcile`, a structural clone of `FicSyncDocuments.php:24-58`.
- `app/Support/Fic/FicReconcileService.php` — `reconcileAll(): array` returning `{matched, linked, pdfs_attached, candidates}` counts, mirroring `FicSyncService::syncAll()`.
- `app/Support/Fic/FicClient.php` — add **one** new method for binary PDF retrieval (see below). No other client change.
- Schedule in `app/Console/Kernel.php`, right after the FiC block:
  ```php
  $schedule->command('fic:reconcile')->dailyAt('03:15')->withoutOverlapping();
  ```
  Daily (not hourly) — the match runs off the local mirror and there is no freshness pressure; offset from `fic:sync-cassa` (02:30) so runs never contend.

**Reuse of FicClient / FicRateGuard**
- The **match phase costs zero FiC calls** — it runs entirely against the `fic_documents` mirror (direction = `received`) joined to `suppliers.tax_code`. This is the design's biggest quota win.
- Command top mirrors `FicSyncDocuments`: guard `isConfigured()/hasCompany()`, then **early-return `SUCCESS` when `FicRateGuard::isCoolingDown()`** (`FicSyncDocuments.php:34-38`) so a reconcile never eats into a cooldown `fic:sync` opened.
- PDF fetch is the **only** quota-spending step. Add `FicClient::documentFile(int $ficId): ?string` that (a) does NOT reuse `fetch()` (hard-wired to `->json()`, `:65`) but uses `->body()`, and (b) still wraps the request in `FicRateGuard::guard()` … `FicRateGuard::record($response)` exactly like `fetch()`. A raw `Http::` call here would bypass the shared circuit-breaker — forbidden.

**Supplier P.IVA matching (normalization)**
- Normalize both sides: strip whitespace, uppercase, strip a leading `IT` country prefix. Then accept a match **only** when the normalized value is exactly 11 numeric digits (`/^\d{11}$/`).
- Reject the noise the live data contains: `""`, `"ESTERO"`, foreign VATs (`EU…`, ES `B…`), placeholders (`XXXXXXX1`). These are silently skipped, not linked.
- Match `fic_documents.entity_vat` → `suppliers.tax_code` → `Supplier::assets()` (`Supplier.php:454-457`). Caveat to encode: `tax_code` is nominally codice fiscale, `entity_vat` is partita IVA — identical 11-digit for Italian companies, divergent for individuals; the 11-digit-numeric gate is what makes the match safe.

**Idempotency on fic_invoice_id**
- Natural key = FiC's document `fic_id`, stored in `_snipeit_fic_invoice_id_32`. Resolve the column dynamically via `CustomField::where('db_column', …)`/by name — do **not** hard-code the `_32` suffix.
- Gate the **entire** per-(asset, invoice) op — field write **and** PDF attach — on `_snipeit_fic_invoice_id_32` already equalling this `fic_id`; if set, skip both. `handleFile()` appends `str_random(8)` to filenames (`UploadFileRequest.php:50`), so the attachment can **not** be deduped by filename — the marker is the only reliable gate. Perform the write+attach inside one transaction so a re-run can never double-attach.

**PDF fetch + attach (respecting the shared quota)**
- Fetch a PDF **only** for a freshly-linked asset (marker just written), **once per document**, capped per run (e.g. `--pdf-limit=25`). Never re-fetch on subsequent runs.
- Note the header mirror stores no `attachment_token`; getting one requires a detailed single-document fetch — treat the PDF step as **≥1 extra shared call per invoice** and budget accordingly under `HOURLY_FLOOR=50`/`MONTHLY_FLOOR=2000`.
- Attach via the native asset path, not a compliance `Document`: write bytes with `Storage::put($map_storage_path['assets'].$name, $bytes)` (bypassing `handleFile`, which expects an HTTP `UploadedFile`), then `$asset->logUpload($fileName, $note, FileIntegrity::metadataForStoredFile(...))` (`Loggable.php:459-484`; Asset already `use HasUploads`). Put the `fic_id` in the upload `note` for a second dedupe signal.

**Error handling / logging (copy exactly)**
- `try` → service call; `catch FicRateLimitException` → `$this->warn(...)` + return `SUCCESS` (cooldown is normal, not failure); `catch \Throwable` → `$this->error(...)` **plus the grep-able** `Log::error('FiC reconcile failed: '…, ['exception' => $e])` line, best-effort tenant mail, return `FAILURE`. Same log destination and monitoring as the rest of the `fic:*` family.

**Multi-tenant / company scoping**
- Every mirror query and every asset write is clamped by `company_id`; carry `fic_documents.company_id` through the match and never write an asset outside its tenant. Custom columns 32–37 already exist (additive), so no migration is needed for phase 1. `withoutOverlapping()` + the cooldown early-return together guarantee a slow reconcile never overlaps `fic:sync`.

## 4. The open business decision (blocks the write phase)

**How does a FiC purchase invoice map to a specific asset?** The data does not answer this: VAT→supplier is one-to-many, the mirror stores header fields only (no line items / `items_list`), and coverage is tiny (207 distinct VATs, 8 match a `tax_code`, 5 of those suppliers own assets). Per-line matching would require a fresh detailed API fetch per invoice and still usually lacks asset serials.

Options:
- **(a) Supplier-level, human-confirmed (RECOMMENDED default).** Command auto-links only the unambiguous sliver (supplier owns exactly one asset), and emits every other VAT-matched invoice as a **candidates list** for a human to confirm; confirmation writes the `fic_*` fields + attaches the PDF. Honest to the data, safe on prod, no guessing.
- (b) Fully-automatic 1:1 — rejected: requires a rule ("one asset per invoice") that is false in this data; would mislink or no-op most invoices.
- (c) Line-item matching — rejected for phase 1: extra per-invoice quota, and line items rarely carry serials.

**Data-model consequence to decide now:** the single `_snipeit_fic_invoice_id_32` field holds **one** invoice per asset. If the confirmed reality is many-invoices-per-asset or one-invoice-funds-many-assets, add a `fic_document_asset` pivot with unique `(fic_document_id, asset_id)` — mirroring `fic_documents_unique`. Decide the cardinality before building the confirmation surface.

**Which asset models carry the FiC fieldset:** attach the global `FiC` fieldset only to the asset models that represent capitalized purchases (cespiti / beni) that actually come from FiC suppliers — not every model. Attachment affects only edit-form UI rendering, not column existence, so this is a low-risk, reversible UI decision that should follow the cardinality decision.

## 5. Phased, low-risk build plan

1. **Phase 0 — dry-run report (no writes).** Ship `fic:reconcile --dry-run` that runs the normalized VAT→supplier→asset match against the mirror and prints/logs: matched invoices, unambiguous auto-linkable pairs, and the ambiguous candidates list — with zero FiC calls and zero DB writes. This validates the match quality and surfaces the real coverage to the team before touching prod.
2. **Phase 1 — write custom fields (no PDF).** Enable idempotent writes of the six `fic_*` fields for the auto-linkable unambiguous cases, gated on the marker, `company_id`-clamped, in a transaction. Still zero FiC quota spent. Verify on a single tenant first.
3. **Phase 2 — human-confirmation surface + PDF attach.** Add the thin manual-link UI (or, minimally, an admin action over the candidates list). Only on confirm: fetch the PDF once via the new guarded `FicClient::documentFile()`, capped per run, and attach via `logUpload`. This is the first step that spends shared quota — introduce it last, behind the cooldown early-return.
4. **Phase 3 (conditional) — pivot table.** If the cardinality decision (§4) is not 1:1, add `fic_document_asset` and migrate the marker logic to the pivot.

**Tie to deferred token-hardening:** this architecture **removes the dependency** on it. Because writes are in-process against the existing app DB creds and reads use the single existing read-only FiC token, there is no external service needing Asset write credentials — so the deferred Passport/no-per-token-scopes work stays deferred and out of the critical path. Only revisit it if a *third* consumer or the separate-service architecture (B) is ever forced by new requirements; nothing in this plan requires it.
---

## Appendice — Fase 0: risultati reali (dry-run su produzione, 2026-08-12)

Comando `php artisan fic:reconcile` (sola lettura, 0 chiamate FiC), sui dati veri:

| Metrica | Valore |
|---|---|
| Documenti ricevuti (acquisti) | 1910 |
| P.IVA distinte (grezze) | 206 |
| Fatture con P.IVA IT valida | 1264 |
| Fatture la cui P.IVA matcha un fornitore | **170** |
| Auto-collegabili (fornitore con 1 solo asset) | **0** |
| Da confermare (fornitore con più asset) | **0** |
| Match senza asset (fornitore con 0 asset) | **4** — ArcoLink (54 fatt.), Iliad (50), Genesys (21), Enel (2) |
| P.IVA su più fornitori (da consolidare) | **2** — Italway (#20+#61), Telecom Italia (#3+#38) |

**Lettura del dato (informa la decisione di mapping):** oggi l'auto-collegamento fattura→asset per P.IVA rende **~0**, perché i fornitori che compaiono negli acquisti FiC (telco/utility/servizi) **non possiedono asset**, mentre i vendor hardware che possiedono asset o non hanno la P.IVA valorizzata o non compaiono negli acquisti. Conseguenze pratiche **prima** di costruire la fase di scrittura:
1. **Consolidare i 2 fornitori duplicati** (Italway, Telecom Italia) — stessa P.IVA su due record.
2. **Popolare `tax_code` (P.IVA)** sui vendor hardware che possiedono cespiti (lega a P1 — anagrafiche reali).
3. **Verificare `supplier_id`** sugli asset (che i cespiti siano collegati al fornitore d'acquisto).
Solo dopo questa prep il match diventa produttivo. È esattamente il valore della Fase 0: misurare prima di scrivere.
