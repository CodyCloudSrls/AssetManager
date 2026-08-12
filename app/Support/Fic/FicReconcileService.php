<?php

namespace App\Support\Fic;

use App\Models\FicDocument;
use Illuminate\Support\Facades\DB;

/**
 * FiC → Asset reconciliation, PHASE 0 (read-only report).
 *
 * Matches received (purchase) FiC documents to Asset suppliers on the Italian VAT/tax code,
 * working ENTIRELY off the local `fic_documents` mirror + `suppliers`/`assets` tables — it makes
 * ZERO Fatture in Cloud API calls and performs ZERO writes. Its only job is to quantify how much
 * of the invoice→asset link can be resolved and to surface the ambiguous cases, so the team can
 * decide the mapping rule (supplier-level vs pivot) before any write phase is built.
 *
 * Strictly unidirectional (FiC is the source); nothing here mutates FiC or the Asset DB.
 */
class FicReconcileService
{
    /**
     * Normalise an Italian VAT / codice fiscale for matching. Returns the canonical 11-digit
     * partita IVA, or null when the value is not a valid IT VAT (foreign VAT, "ESTERO", empty,
     * placeholders like "XXXXXXX1", CF of an individual, etc.) — those are intentionally skipped.
     */
    public static function normalizeVat(?string $raw): ?string
    {
        $v = strtoupper(trim((string) $raw));
        $v = preg_replace('/\s+/', '', $v);   // FiC/supplier values are often space-padded
        $v = preg_replace('/^IT/', '', $v);   // drop the optional IT country prefix

        return preg_match('/^\d{11}$/', $v) === 1 ? $v : null;
    }

    /**
     * @return array{
     *   received_docs:int, distinct_vat_raw:int, normalizable_invoices:int, matched_invoices:int,
     *   matched_vat:int, auto_linkable:array, candidates:array, matched_no_asset:array,
     *   ambiguous_supplier:array
     * }
     */
    public function report(): array
    {
        // Supplier lookup keyed by normalised VAT (detect two suppliers sharing one VAT).
        $supplierMap = [];
        foreach (DB::table('suppliers')->whereNull('deleted_at')
            ->whereNotNull('tax_code')->where('tax_code', '<>', '')
            ->get(['id', 'name', 'tax_code', 'company_id']) as $supplier) {
            $vat = self::normalizeVat($supplier->tax_code);
            if ($vat !== null) {
                $supplierMap[$vat][] = $supplier;
            }
        }

        // Received (purchase) mirror rows grouped by normalised counterparty VAT.
        $docVat = [];
        $distinctVatRaw = [];
        $normalizableInvoices = 0;
        foreach (DB::table('fic_documents')->where('direction', FicDocument::DIRECTION_RECEIVED)
            ->get(['fic_id', 'entity_vat', 'entity_name']) as $doc) {
            if ($doc->entity_vat !== null && trim($doc->entity_vat) !== '') {
                $distinctVatRaw[trim($doc->entity_vat)] = true;
            }
            $vat = self::normalizeVat($doc->entity_vat);
            if ($vat === null) {
                continue;
            }
            $normalizableInvoices++;
            $docVat[$vat]['invoices'] = ($docVat[$vat]['invoices'] ?? 0) + 1;
            $docVat[$vat]['name'] = $doc->entity_name;
        }

        $autoLinkable = $candidates = $matchedNoAsset = $ambiguousSupplier = [];
        $matchedInvoices = 0;

        foreach ($docVat as $vat => $info) {
            if (! isset($supplierMap[$vat])) {
                continue; // VAT valid but no supplier carries it -> skipped (nothing to link to)
            }

            $matchedInvoices += $info['invoices'];
            $suppliers = $supplierMap[$vat];

            // A P.IVA is a string identifier (leading zeros matter); PHP coerces a purely-numeric
            // array key to int, so cast it back for the output rows.
            $vatStr = (string) $vat;

            // Two suppliers with the same VAT -> can't auto-pick; flag for cleanup.
            if (count($suppliers) > 1) {
                $ambiguousSupplier[] = [
                    'vat' => $vatStr,
                    'invoices' => $info['invoices'],
                    'suppliers' => array_map(fn ($s) => ['id' => $s->id, 'name' => $s->name], $suppliers),
                ];

                continue;
            }

            $supplier = $suppliers[0];
            $assetCount = DB::table('assets')->where('supplier_id', $supplier->id)->whereNull('deleted_at')->count();
            $row = [
                'vat' => $vatStr,
                'supplier_id' => $supplier->id,
                'supplier' => $supplier->name,
                'invoices' => $info['invoices'],
                'assets' => $assetCount,
            ];

            if ($assetCount === 1) {
                $row['asset_id'] = DB::table('assets')->where('supplier_id', $supplier->id)->whereNull('deleted_at')->value('id');
                $autoLinkable[] = $row;          // unambiguous: supplier owns exactly one asset
            } elseif ($assetCount === 0) {
                $matchedNoAsset[] = $row;         // matched supplier owns no asset -> nothing to link yet
            } else {
                $candidates[] = $row;             // supplier owns many assets -> needs human confirmation
            }
        }

        return [
            'received_docs' => DB::table('fic_documents')->where('direction', FicDocument::DIRECTION_RECEIVED)->count(),
            'distinct_vat_raw' => count($distinctVatRaw),
            'normalizable_invoices' => $normalizableInvoices,
            'matched_invoices' => $matchedInvoices,
            'matched_vat' => count($autoLinkable) + count($candidates) + count($matchedNoAsset) + count($ambiguousSupplier),
            'auto_linkable' => $autoLinkable,
            'candidates' => $candidates,
            'matched_no_asset' => $matchedNoAsset,
            'ambiguous_supplier' => $ambiguousSupplier,
        ];
    }
}
