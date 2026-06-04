<?php

namespace App\Support\Exports;

use App\Models\Tenant;
use App\Models\TenantService;
use RuntimeException;
use ZipArchive;

class AcnTenantServicesXlsxExporter
{
    private const HEADERS = [
        'Macro-area',
        'Denominazione Attività/Servizio',
        'Descrizione',
        'Categoria di rilevanza pre-assegnata',
        'Categoria di rilevanza attribuita',
    ];

    public function build(Tenant $tenant): string
    {
        $services = $tenant->services()
            ->active()
            ->orderBy('macro_area')
            ->orderBy('name')
            ->get();

        $rows = $services->map(fn (TenantService $service) => [
            $service->acn_macro_area_label,
            $service->name,
            $service->description,
            $service->acn_pre_assigned_relevance_label,
            $service->acn_assigned_relevance_override_label,
        ])->values()->all();

        if ($rows === []) {
            $rows[] = ['', '', '', '', ''];
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'acn-tenant-services-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to prepare ACN tenant services export.');
        }

        $zip = new ZipArchive();
        if ($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to create ACN tenant services XLSX export.');
        }

        $this->addWorkbookFiles($zip, $rows);
        $zip->close();

        return $temporaryPath;
    }

    private function addWorkbookFiles(ZipArchive $zip, array $rows): void
    {
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->servicesWorksheetXml($rows));
        $zip->addFromString('xl/worksheets/sheet2.xml', $this->instructionsWorksheetXml());
    }

    private function servicesWorksheetXml(array $rows): string
    {
        $rowXml = [$this->rowXml(1, self::HEADERS, 1)];
        $rowNumber = 2;

        foreach ($rows as $row) {
            $rowXml[] = $this->rowXml($rowNumber, $row, 2);
            $rowNumber++;
        }

        $lastRow = max(2, count($rows) + 1);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="A1:E'.$lastRow.'"/>'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="1" width="36" customWidth="1"/><col min="2" max="2" width="42" customWidth="1"/><col min="3" max="3" width="52" customWidth="1"/><col min="4" max="5" width="34" customWidth="1"/></cols>'
            .'<sheetData>'.implode('', $rowXml).'</sheetData>'
            .'<autoFilter ref="A1:E'.$lastRow.'"/>'
            .'</worksheet>';
    }

    private function instructionsWorksheetXml(): string
    {
        $rows = [
            ['Campo', 'Indicazione'],
            ['Macro-area', 'Selezionare la macro-area ACN applicabile al servizio erogato.'],
            ['Denominazione Attività/Servizio', 'Campo obbligatorio. Usare una denominazione chiara e univoca per il soggetto.'],
            ['Descrizione', 'Campo facoltativo per chiarire il perimetro del servizio.'],
            ['Categoria di rilevanza pre-assegnata', 'Valore determinato dalla macro-area.'],
            ['Categoria di rilevanza attribuita', 'Compilare solo quando viene attribuita una categoria diversa da quella pre-assegnata.'],
        ];

        $rowXml = [];
        foreach ($rows as $index => $row) {
            $rowXml[] = $this->rowXml($index + 1, $row, $index === 0 ? 1 : 2);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="A1:B'.count($rows).'"/>'
            .'<cols><col min="1" max="1" width="34" customWidth="1"/><col min="2" max="2" width="92" customWidth="1"/></cols>'
            .'<sheetData>'.implode('', $rowXml).'</sheetData>'
            .'</worksheet>';
    }

    private function rowXml(int $rowNumber, array $values, int $style): string
    {
        $cells = [];

        foreach (array_values($values) as $index => $value) {
            $cells[] = $this->cellXml($this->columnName($index + 1).$rowNumber, $value, $style);
        }

        return '<row r="'.$rowNumber.'">'.implode('', $cells).'</row>';
    }

    private function cellXml(string $reference, mixed $value, int $style): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '<c r="'.$reference.'" s="'.$style.'"/>';
        }

        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t>'.$this->escapeXml($value).'</t></is></c>';
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="Elenco categorizzato" sheetId="1" r:id="rId1"/>'
            .'<sheet name="Istruzioni" sheetId="2" r:id="rId2"/>'
            .'</sheets>'
            .'</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9EAF7"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border/><border><left style="thin"><color rgb="FFD9D9D9"/></left><right style="thin"><color rgb="FFD9D9D9"/></right><top style="thin"><color rgb="FFD9D9D9"/></top><bottom style="thin"><color rgb="FFD9D9D9"/></bottom></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function corePropertiesXml(): string
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>Elenco categorizzato servizi ACN</dc:title>'
            .'<dc:creator>CodyCloud</dc:creator>'
            .'<cp:lastModifiedBy>CodyCloud</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function appPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>CodyCloud</Application>'
            .'<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>2</vt:i4></vt:variant></vt:vector></HeadingPairs>'
            .'<TitlesOfParts><vt:vector size="2" baseType="lpstr"><vt:lpstr>Elenco categorizzato</vt:lpstr><vt:lpstr>Istruzioni</vt:lpstr></vt:vector></TitlesOfParts>'
            .'</Properties>';
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
