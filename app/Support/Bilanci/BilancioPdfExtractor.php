<?php

namespace App\Support\Bilanci;

use Illuminate\Support\Facades\Process;
use Smalot\PdfParser\Parser;

/**
 * Extracts the Conto Economico figures from a deposited bilancio PDF (the standardized
 * "itcc" XBRL taxonomy layout used by the Registro Imprese). Handles the owner-locked
 * (empty user password) encryption those PDFs carry via Ghostscript's txtwrite; falls back
 * to smalot/pdfparser for plain PDFs. Amounts are returned as read from the current-year
 * column — the caller reviews them before saving (no blind overwrite).
 */
class BilancioPdfExtractor
{
    /** @return array<string, int|float|null> keys: anno, ricavi, costi, costo_personale, ammortamenti, utile, imposte */
    public function extract(string $absolutePath): array
    {
        $text = $this->extractText($absolutePath);

        return $this->parse($text);
    }

    /**
     * Parse already-extracted plain text into the Conto Economico figures (the core logic,
     * independent of Ghostscript/PDF — used directly in tests).
     *
     * @return array<string, int|float|null>
     */
    public function parseText(string $text): array
    {
        return $this->parse($text);
    }

    private function extractText(string $absolutePath): string
    {
        // Registro Imprese PDFs are usually owner-locked; Ghostscript reads them (empty user
        // password) and its txtwrite device gives clean, layout-ordered text.
        $gsText = $this->extractWithGhostscript($absolutePath);
        if (trim($gsText) !== '') {
            return $gsText;
        }

        // Plain PDF fallback.
        try {
            return (new Parser)->parseFile($absolutePath)->getText();
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function extractWithGhostscript(string $absolutePath): string
    {
        $out = tempnam(sys_get_temp_dir(), 'bilancio_').'.txt';

        try {
            $result = Process::timeout(60)->run([
                'gs', '-q', '-dNOPAUSE', '-dBATCH', '-dSAFER',
                '-sDEVICE=txtwrite', '-sOutputFile='.$out, $absolutePath,
            ]);

            // gs prints a harmless "finalizing subclassing device" note on some files but still
            // writes the text; rely on the output file, not the exit code.
            $text = is_file($out) ? (string) file_get_contents($out) : '';
        } catch (\Throwable $e) {
            $text = '';
        } finally {
            if (is_file($out)) {
                @unlink($out);
            }
        }

        return $text;
    }

    /** @return array<string, int|float|null> */
    private function parse(string $text): array
    {
        return [
            'anno' => $this->parseYear($text),
            'ricavi' => $this->amountAfter($text, 'Totale valore della produzione'),
            'costi' => $this->amountAfter($text, 'Totale costi della produzione'),
            'costo_personale' => $this->amountAfter($text, 'Totale costi per il personale')
                ?? $this->amountAfter($text, '9\)\s*per il personale'),
            'ammortamenti' => $this->amountAfter($text, 'Totale ammortamenti e svalutazioni'),
            'imposte' => $this->amountAfter($text, 'Totale delle imposte sul reddito'),
            'utile' => $this->amountAfter($text, 'Utile \(perdita\) dell.esercizio'),
        ];
    }

    private function parseYear(string $text): ?int
    {
        if (preg_match('/al\s+\d{2}-\d{2}-(\d{4})/', $text, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(\d{4})-\d{2}-\d{2}\s+\d{4}-\d{2}-\d{2}/', $text, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/\d{2}-\d{2}-(\d{4})\s+\d{2}-\d{2}-\d{4}/', $text, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * The first amount that follows a Conto Economico label (the current-year column).
     * Italian number format: '.' thousands separator, ',' decimal; parentheses/'-' = negative.
     */
    private function amountAfter(string $text, string $labelRegex): int|float|null
    {
        // Allow trailing label words after the anchor (e.g. "…sul reddito dell'esercizio,
        // correnti, …  112") before the current-year number, but stay on the same line and
        // keep any leading '(' so a parenthesised (negative) amount is captured whole.
        if (! preg_match('/'.$labelRegex.'[^\d\n(]*(\(?-?[\d.]+(?:,\d+)?\)?)/u', $text, $m)) {
            return null;
        }

        return $this->parseAmount($m[1]);
    }

    private function parseAmount(string $raw): int|float
    {
        $s = trim($raw);
        $negative = false;

        if (preg_match('/^\((.*)\)$/', $s, $m)) {
            $negative = true;
            $s = $m[1];
        }
        if (str_starts_with($s, '-')) {
            $negative = true;
            $s = ltrim($s, '-');
        }

        $s = str_replace('.', '', $s);   // thousands separator
        $s = str_replace(',', '.', $s);  // decimal separator
        $value = (float) $s;

        // Whole amounts come back as int (cleaner); only genuine cents stay float.
        if ($value == floor($value) && abs($value) < PHP_INT_MAX) {
            $value = (int) $value;
        }

        return $negative ? -$value : $value;
    }
}
