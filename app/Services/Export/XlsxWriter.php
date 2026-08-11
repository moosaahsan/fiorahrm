<?php

namespace App\Services\Export;

use ZipArchive;

/**
 * Writes a real .xlsx workbook without a spreadsheet library.
 *
 * An xlsx file is a zip of XML parts, and everything this app exports is
 * tabular data with a bold header — so the handful of parts below cover it.
 * PhpSpreadsheet would be the obvious choice, but it requires ext-gd, which is
 * not available here or on the build runner; this needs only ZipArchive.
 *
 * Strings are written inline (t="inlineStr") rather than through a shared
 * string table. That costs a few bytes on repetitive data and saves a whole
 * indirection, which is the right trade for exports of this size.
 *
 *   $writer = new XlsxWriter();
 *   $writer->addSheet('Profile', [['Field', 'Value'], ['Name', 'Ali']]);
 *   $writer->save($path);
 */
class XlsxWriter
{
    /** @var array<int, array{name: string, rows: array<int, array<int, mixed>>, widths: array<int, float>}> */
    protected array $sheets = [];

    /**
     * Add a sheet. The first row is styled as a header.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, float>  $widths  Column widths in characters, by column index
     */
    public function addSheet(string $name, array $rows, array $widths = []): static
    {
        $this->sheets[] = [
            'name' => $this->sanitiseSheetName($name),
            'rows' => array_values($rows),
            'widths' => $widths,
        ];

        return $this;
    }

    public function hasSheets(): bool
    {
        return $this->sheets !== [];
    }

    /**
     * Write the workbook to disk.
     *
     * @throws \RuntimeException when the archive cannot be created
     */
    public function save(string $path): void
    {
        if (! $this->hasSheets()) {
            // Excel rejects a workbook with no sheets.
            $this->addSheet('Sheet1', []);
        }

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Could not create the workbook at {$path}.");
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());

        foreach ($this->sheets as $index => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($index + 1) . '.xml', $this->sheetXml($sheet));
        }

        $zip->close();
    }

    // ──────────────────────────────────────────────
    // Parts
    // ──────────────────────────────────────────────

    protected function contentTypes(): string
    {
        $overrides = '';

        foreach ($this->sheets as $index => $sheet) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . ($index + 1) . '.xml"'
                . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides
            . '</Types>';
    }

    protected function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    protected function workbook(): string
    {
        $sheets = '';

        foreach ($this->sheets as $index => $sheet) {
            $sheets .= '<sheet name="' . $this->escape($sheet['name']) . '"'
                . ' sheetId="' . ($index + 1) . '"'
                . ' r:id="rId' . ($index + 1) . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets>'
            . '</workbook>';
    }

    protected function workbookRels(): string
    {
        $rels = '';

        foreach ($this->sheets as $index => $sheet) {
            $rels .= '<Relationship Id="rId' . ($index + 1) . '"'
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                . ' Target="worksheets/sheet' . ($index + 1) . '.xml"/>';
        }

        // The styles part is numbered after the sheets so ids stay unique.
        $rels .= '<Relationship Id="rId' . (count($this->sheets) + 1) . '"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            . ' Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    /**
     * Two cell formats: 0 is plain, 1 is the bold header on a light fill.
     */
    protected function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF1F2937"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEEF2FF"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    /**
     * @param  array{name: string, rows: array<int, array<int, mixed>>, widths: array<int, float>}  $sheet
     */
    protected function sheetXml(array $sheet): string
    {
        $cols = '';

        foreach ($sheet['widths'] as $index => $width) {
            $column = $index + 1;
            $cols .= '<col min="' . $column . '" max="' . $column . '" width="' . $width . '" customWidth="1"/>';
        }

        $rowsXml = '';

        foreach ($sheet['rows'] as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $isHeader = $rowIndex === 0;
            $cells = '';

            foreach (array_values($row) as $colIndex => $value) {
                $cells .= $this->cellXml($this->columnName($colIndex) . $rowNumber, $value, $isHeader);
            }

            $rowsXml .= '<row r="' . $rowNumber . '">' . $cells . '</row>';
        }

        // Keep the header visible while scrolling.
        $freeze = count($sheet['rows']) > 1
            ? '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $freeze
            . ($cols !== '' ? '<cols>' . $cols . '</cols>' : '')
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '</worksheet>';
    }

    protected function cellXml(string $reference, mixed $value, bool $isHeader): string
    {
        $style = $isHeader ? ' s="1"' : '';

        if ($value === null || $value === '') {
            return '<c r="' . $reference . '"' . $style . '/>';
        }

        if ($this->isNumeric($value)) {
            return '<c r="' . $reference . '"' . $style . '><v>' . (0 + $value) . '</v></c>';
        }

        return '<c r="' . $reference . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">'
            . $this->escape((string) $value)
            . '</t></is></c>';
    }

    /**
     * Only treat a value as a number when Excel showing it as one is what the
     * reader wants. Account numbers, CNICs and phone numbers are digit strings
     * whose leading zeros matter, so they stay text.
     */
    protected function isNumeric(mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            return true;
        }

        if (! is_string($value) || ! is_numeric($value)) {
            return false;
        }

        return ! str_starts_with($value, '0') || $value === '0';
    }

    protected function columnName(int $index): string
    {
        $name = '';

        for ($i = $index; $i >= 0; $i = intdiv($i, 26) - 1) {
            $name = chr(65 + ($i % 26)) . $name;
        }

        return $name;
    }

    /**
     * Excel rejects these characters in a sheet name, and caps it at 31 chars.
     */
    protected function sanitiseSheetName(string $name): string
    {
        $name = str_replace(['\\', '/', '*', '?', ':', '[', ']'], ' ', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return mb_substr($name !== '' ? $name : 'Sheet', 0, 31);
    }

    protected function escape(string $value): string
    {
        // Control characters are not valid in XML 1.0 and make Excel refuse the file.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? $value;

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
