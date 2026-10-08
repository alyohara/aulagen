<?php

namespace App\Services\Processors;

use App\Enums\DocumentType;
use App\Models\Document;

/**
 * Extractor XLSX basado en ZipArchive + XML de la hoja de cálculo.
 * Convierte cada fila en "columna: valor | columna: valor".
 */
class XlsxExtractor implements Extractor
{
    public function supports(DocumentType $type): bool
    {
        return $type === DocumentType::Xlsx;
    }

    public function extract(Document $document, string $absolutePath): ExtractionResult
    {
        $zip = new \ZipArchive();

        if ($zip->open($absolutePath) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo XLSX.');
        }

        $shared = [];

        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $dom = new \DOMDocument();
            if (@$dom->loadXML(preg_replace('/&(?!#?\w+;)/', '&amp;', (string) $sharedXml))) {
                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($xpath->query('//x:si') as $item) {
                    $shared[] = trim($item->textContent);
                }
            }
        }

        $sheetName = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->statIndex($i)['name'] ?? '';
            if (preg_match('#^xl/worksheets/sheet1\.xml$#', $name)) {
                $sheetName = $name;
                break;
            }
        }

        if ($sheetName === null) {
            $zip->close();
            throw new \RuntimeException('El XLSX no contiene hojas legibles.');
        }

        $sheetXml = $zip->getFromName($sheetName);
        $zip->close();

        $dom = new \DOMDocument();
        if (! @$dom->loadXML(preg_replace('/&(?!#?\w+;)/', '&amp;', (string) $sheetXml))) {
            throw new \RuntimeException('No se pudo interpretar la hoja del XLSX.');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $blocks = [];
        $lines = [];

        foreach ($xpath->query('//x:row') as $row) {
            $cells = [];

            foreach ($xpath->query('./x:c', $row) as $cell) {
                $reference = $cell->attributes->getNamedItem('r')?->nodeValue ?? '';
                $type = $cell->attributes->getNamedItem('t')?->nodeValue ?? '';
                $value = '';

                if ($type === 'inlineStr') {
                    $value = trim($cell->textContent);
                } else {
                    $v = $xpath->query('./x:v', $cell)->item(0)?->textContent ?? '';
                    $value = $type === 's' ? ($shared[(int) $v] ?? '') : $v;
                }

                if ($value !== '') {
                    $column = preg_replace('/\d+/', '', $reference) ?: '';
                    $cells[$column] = $value;
                }
            }

            if ($cells === []) {
                continue;
            }

            $line = implode(' | ', array_map(fn ($col, $val) => $col.': '.$val, array_keys($cells), array_values($cells)));
            $lines[] = $line;
            $blocks[] = ['text' => $line, 'page' => null, 'section' => 'Hoja 1'];
        }

        return new ExtractionResult(
            text: trim(implode("\n", $lines)),
            blocks: $blocks,
            headings: [],
            pages: null,
            meta: ['parser' => 'zip+xml', 'format' => 'xlsx', 'rows' => count($lines)],
        );
    }
}
