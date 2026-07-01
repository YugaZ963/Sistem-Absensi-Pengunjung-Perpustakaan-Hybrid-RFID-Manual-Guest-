<?php
declare(strict_types=1);

function xlsx_download(string $filename, array $rows, string $sheetName = 'Sheet1', array $options = []): void
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive extension is required for XLSX export.');
    }

    $tmpRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'absensi_xlsx_' . bin2hex(random_bytes(8));
    $packageDir = $tmpRoot . DIRECTORY_SEPARATOR . 'package';
    $zipPath = $tmpRoot . DIRECTORY_SEPARATOR . 'export.xlsx';

    xlsx_mkdir($packageDir . DIRECTORY_SEPARATOR . '_rels');
    xlsx_mkdir($packageDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . '_rels');
    xlsx_mkdir($packageDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'worksheets');

    $sheetXml = xlsx_build_sheet_xml($rows, $options);
    file_put_contents($packageDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'worksheets' . DIRECTORY_SEPARATOR . 'sheet1.xml', $sheetXml);
    file_put_contents($packageDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'workbook.xml', xlsx_workbook_xml($sheetName));
    file_put_contents($packageDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . '_rels' . DIRECTORY_SEPARATOR . 'workbook.xml.rels', xlsx_workbook_rels_xml());
    file_put_contents($packageDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'styles.xml', xlsx_styles_xml());
    file_put_contents($packageDir . DIRECTORY_SEPARATOR . '_rels' . DIRECTORY_SEPARATOR . '.rels', xlsx_rels_xml());
    file_put_contents($packageDir . DIRECTORY_SEPARATOR . '[Content_Types].xml', xlsx_content_types_xml());

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        xlsx_delete_dir($tmpRoot);
        throw new RuntimeException('Failed to create XLSX package.');
    }

    $files = [
        '[Content_Types].xml',
        '_rels/.rels',
        'xl/workbook.xml',
        'xl/_rels/workbook.xml.rels',
        'xl/styles.xml',
        'xl/worksheets/sheet1.xml',
    ];

    foreach ($files as $file) {
        $fullPath = $packageDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
        $zip->addFile($fullPath, $file);
    }
    $zip->close();

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) ?: 'export.xlsx';
    if (!preg_match('/\.xlsx$/i', $safeName)) {
        $safeName .= '.xlsx';
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Content-Length: ' . (string) filesize($zipPath));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($zipPath);
    xlsx_delete_dir($tmpRoot);
    exit;
}

function xlsx_build_sheet_xml(array $rows, array $options = []): string
{
    $rowXml = '';
    $maxColumn = 0;

    foreach ($rows as $rowIndex => $row) {
        $excelRow = $rowIndex + 1;
        $cellsXml = '';

        foreach ((array) $row as $colIndex => $value) {
            $style = 0;
            $type = 's';
            $text = '';

            if (is_array($value)) {
                $text = (string) ($value['value'] ?? '');
                $style = (int) ($value['style'] ?? 0);
                $type = (string) ($value['type'] ?? 's');
            } else {
                $text = (string) $value;
            }

            if ($text === '' && $style === 0) {
                continue;
            }

            $columnNumber = ((int) $colIndex) + 1;
            if ($columnNumber > $maxColumn) {
                $maxColumn = $columnNumber;
            }

            $cellRef = xlsx_column_name($columnNumber) . $excelRow;
            $styleAttr = $style > 0 ? ' s="' . $style . '"' : '';

            if ($type === 'n' && is_numeric($text)) {
                $cellsXml .= '<c r="' . $cellRef . '"' . $styleAttr . '><v>' . $text . '</v></c>';
            } else {
                $cellsXml .= '<c r="' . $cellRef . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">' . xlsx_xml_escape($text) . '</t></is></c>';
            }
        }

        $rowXml .= '<row r="' . $excelRow . '">' . $cellsXml . '</row>';
    }

    $colsXml = xlsx_build_columns_xml((array) ($options['column_widths'] ?? []), $maxColumn);
    $mergesXml = xlsx_build_merges_xml((array) ($options['merges'] ?? []));

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . $colsXml
        . '<sheetData>' . $rowXml . '</sheetData>'
        . $mergesXml
        . '</worksheet>';
}

function xlsx_column_name(int $column): string
{
    $name = '';
    while ($column > 0) {
        $column--;
        $name = chr(($column % 26) + 65) . $name;
        $column = intdiv($column, 26);
    }
    return $name;
}

function xlsx_xml_escape(string $value): string
{
    return str_replace(
        ['&', '<', '>', '"', "'"],
        ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'],
        $value
    );
}

function xlsx_content_types_xml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>';
}

function xlsx_rels_xml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';
}

function xlsx_workbook_xml(string $sheetName): string
{
    $safeSheetName = trim($sheetName) !== '' ? trim($sheetName) : 'Sheet1';
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="' . xlsx_xml_escape($safeSheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>';
}

function xlsx_workbook_rels_xml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';
}

function xlsx_styles_xml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="3">'
        . '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="14"/><color rgb="FF1F2D3D"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FF1F2D3D"/><name val="Calibri"/><family val="2"/></font>'
        . '</fonts>'
        . '<fills count="4">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFDCEAF9"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF1FB"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2">'
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border><left style="thin"><color rgb="FF9AA7B8"/></left><right style="thin"><color rgb="FF9AA7B8"/></right><top style="thin"><color rgb="FF9AA7B8"/></top><bottom style="thin"><color rgb="FF9AA7B8"/></bottom><diagonal/></border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="6">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
}

function xlsx_build_columns_xml(array $widths, int $maxColumn): string
{
    if ($widths === []) {
        return '';
    }

    $cols = '';
    for ($i = 1; $i <= $maxColumn; $i++) {
        if (!isset($widths[$i])) {
            continue;
        }
        $width = (float) $widths[$i];
        if ($width <= 0) {
            continue;
        }
        $cols .= '<col min="' . $i . '" max="' . $i . '" width="' . rtrim(rtrim(number_format($width, 2, '.', ''), '0'), '.') . '" customWidth="1"/>';
    }

    if ($cols === '') {
        return '';
    }

    return '<cols>' . $cols . '</cols>';
}

function xlsx_build_merges_xml(array $merges): string
{
    $clean = [];
    foreach ($merges as $merge) {
        $merge = trim((string) $merge);
        if ($merge !== '') {
            $clean[] = $merge;
        }
    }

    if ($clean === []) {
        return '';
    }

    $xml = '<mergeCells count="' . count($clean) . '">';
    foreach ($clean as $merge) {
        $xml .= '<mergeCell ref="' . xlsx_xml_escape($merge) . '"/>';
    }
    $xml .= '</mergeCells>';

    return $xml;
}

function xlsx_mkdir(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
        throw new RuntimeException('Failed to create directory for XLSX export: ' . $path);
    }
}

function xlsx_delete_dir(string $path): void
{
    if (!is_dir($path)) {
        if (is_file($path)) {
            @unlink($path);
        }
        return;
    }

    $items = scandir($path);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $full = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) {
            xlsx_delete_dir($full);
        } else {
            @unlink($full);
        }
    }
    @rmdir($path);
}
