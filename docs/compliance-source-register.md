# Compliance Source Register

Last updated: 2026-05-21

This register formalizes the source-control layer for generated compliance framework packs. It is intentionally conservative: pack content is a bootstrap aid for consultants and tenants, not legal advice or a completeness claim.

## Label Rules

- `EU baseline`: implemented from EU-level legislation or EU institutional guidance only.
- `national overlay`: implemented only when official national transposition or authority material has been verified and recorded here.
- `review required`: official country material may exist, but no expert national content review has been completed for this product pack.
- `missing national source`: no defensible official national source is registered for a country overlay.
- `superseded`: source retained for audit history only and not used for current pack construction.

Country-specific labels must not be used unless the source row is `verified` and the pack scope is `national_overlay`.

## Source Registers

| Register key | Domain | Jurisdiction | Source type | Status | Scope | Last checked | Current pack impact |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `ai_act_eu` | AI Act | EU | EU regulation; European Commission AI Office policy and GPAI guidance | verified | EU baseline | 2026-05-14 | All `ai_act_*` packs |
| `nis2_eu` | NIS2 | EU | EU directive; European Commission transposition status; ENISA overview | verified | EU baseline | 2026-05-14 | `nis2_en`, `nis2_eu_it`, and generated `nis2_*` EU-baseline locale packs |
| `nis2_it` | NIS2 | IT/EU | Italian national transposition; ACN operational material; EU directive; Commission Italy state-of-transposition page | verified | national overlay | 2026-05-14 | `nis2_it_allegato_1`, `nis2_it_allegato_2` |
| `gdpr_eu` | GDPR | EU | EU regulation | verified | EU baseline | 2026-05-14 | All `gdpr_*` packs |

## Official Sources

### AI Act EU

- Regulation (EU) 2024/1689: https://eur-lex.europa.eu/eli/reg/2024/1689/oj
- European Commission AI Act page: https://digital-strategy.ec.europa.eu/en/policies/regulatory-framework-ai
- European Commission GPAI Code of Practice: https://digital-strategy.ec.europa.eu/en/policies/contents-code-gpai
- European Commission GPAI obligations fact page: https://digital-strategy.ec.europa.eu/en/factpages/general-purpose-ai-obligations-under-ai-act

Review note, 2026-05-14: the Commission AI Act page was last updated on 2026-05-11 and records 2026 simplification work and high-risk timeline changes. The product pack remains an EU-baseline evidence scaffold; dates and high-risk applicability must be rechecked before any client-specific advice.

### NIS2 EU

- Directive (EU) 2022/2555: https://eur-lex.europa.eu/eli/dir/2022/2555/oj
- European Commission NIS2 transposition by country: https://digital-strategy.ec.europa.eu/en/policies/nis-transposition
- ENISA NIS2 overview: https://www.enisa.europa.eu/topics/state-of-cybersecurity-in-the-eu/cybersecurity-policies/nis-directive-2

Review note, 2026-05-14: the Commission transposition page exposes country-specific state-of-play pages. Those pages are not treated here as expert-reviewed national overlays. Non-Italian packs therefore remain EU baseline with national `review_required` status.

### NIS2 Italy

- ACN FAQ NIS, aggiornamento delle informazioni: https://www.acn.gov.it/portale/faq/nis/aggiornamento-delle-informazioni
- Decreto legislativo 4 settembre 2024, n. 138, Normattiva: https://www.normattiva.it/uri-res/N2Ls?urn:nir:stato:decreto.legislativo:2024-09-04;138!vig=
- Decreto legislativo 4 settembre 2024, n. 138, Gazzetta Ufficiale: https://www.gazzettaufficiale.it/eli/id/2024/10/01/24G00155/SG
- European Commission Italy implementation page: https://digital-strategy.ec.europa.eu/en/policies/nis2-directive-italy
- Directive (EU) 2022/2555: https://eur-lex.europa.eu/eli/dir/2022/2555/oj

Review note, 2026-05-14: the Italy overlay is limited to an ACN-oriented operational starter pack. ACN web material can change outside code release cycles and must be manually rechecked before filing or client advice.

### GDPR EU

- Regulation (EU) 2016/679: https://eur-lex.europa.eu/eli/reg/2016/679/oj

## AI Act Pack Coverage

All AI Act packs use `source_register_key = ai_act_eu`, `scope = eu_baseline`, `jurisdiction = EU`, `pack_version = 2026.05.14.1`.

Pack keys:

- `ai_act_it`
- `ai_act_en`
- `ai_act_bg_bg`
- `ai_act_ca_es`
- `ai_act_cs_cz`
- `ai_act_da_dk`
- `ai_act_de_de`
- `ai_act_el_gr`
- `ai_act_es_es`
- `ai_act_et_ee`
- `ai_act_fi_fi`
- `ai_act_fr_fr`
- `ai_act_ga_ie`
- `ai_act_hr_hr`
- `ai_act_hu_hu`
- `ai_act_lt_lt`
- `ai_act_lv_lv`
- `ai_act_nl_nl`
- `ai_act_pl_pl`
- `ai_act_pt_pt`
- `ai_act_ro_ro`
- `ai_act_sk_sk`
- `ai_act_sl_si`
- `ai_act_sv_se`

## NIS2 Pack Coverage

The only national overlay currently shipped is Italy, split into `nis2_it_allegato_1` and `nis2_it_allegato_2`. Every other NIS2 jurisdiction remains unshipped and requires manual tenant curation.

| Pack key | Locale | Jurisdiction | Source register | Scope | Status |
| --- | --- | --- | --- | --- | --- |
| `nis2_it_allegato_1` | it-IT | IT/EU | `nis2_it` | national_overlay | verified |
| `nis2_it_allegato_2` | it-IT | IT/EU | `nis2_it` | national_overlay | verified |

## NIS2 Country Overlay Decision Matrix

| Jurisdiction | Status | Pack key | Fallback source register | Release decision |
| --- | --- | --- | --- | --- |
| EU | baseline_only | - | `nis2_eu` | EU baseline only |
| IT | implemented | `nis2_it_allegato_1`, `nis2_it_allegato_2` | `nis2_eu` | Verified national overlay kept as Allegato 1 and Allegato 2 bootstrap packs |
| AT | review_required | - | `nis2_eu` | No national overlay shipped |
| BE | review_required | - | `nis2_eu` | No national overlay shipped |
| BG | review_required | - | `nis2_eu` | No national overlay shipped |
| CY | review_required | - | `nis2_eu` | No national overlay shipped |
| CZ | review_required | - | `nis2_eu` | No national overlay shipped |
| DE | review_required | - | `nis2_eu` | No national overlay shipped |
| DK | review_required | - | `nis2_eu` | No national overlay shipped |
| EE | review_required | - | `nis2_eu` | No national overlay shipped |
| ES | review_required | - | `nis2_eu` | No national overlay shipped |
| FI | review_required | - | `nis2_eu` | No national overlay shipped |
| FR | review_required | - | `nis2_eu` | No national overlay shipped |
| GR | review_required | - | `nis2_eu` | No national overlay shipped |
| HR | review_required | - | `nis2_eu` | No national overlay shipped |
| HU | review_required | - | `nis2_eu` | No national overlay shipped |
| IE | review_required | - | `nis2_eu` | No national overlay shipped |
| LT | review_required | - | `nis2_eu` | No national overlay shipped |
| LU | review_required | - | `nis2_eu` | No national overlay shipped |
| LV | review_required | - | `nis2_eu` | No national overlay shipped |
| MT | review_required | - | `nis2_eu` | No national overlay shipped |
| NL | review_required | - | `nis2_eu` | No national overlay shipped |
| PL | review_required | - | `nis2_eu` | No national overlay shipped |
| PT | review_required | - | `nis2_eu` | No national overlay shipped |
| RO | review_required | - | `nis2_eu` | No national overlay shipped |
| SE | review_required | - | `nis2_eu` | No national overlay shipped |
| SI | review_required | - | `nis2_eu` | No national overlay shipped |
| SK | review_required | - | `nis2_eu` | No national overlay shipped |
