<?php

namespace WordForLaravel\Renderers\Elements;

use DOMElement;
use PhpOffice\PhpWord\SimpleType\TblWidth;

/**
 * Table Renderer
 */
class TableRenderer extends BaseElementRenderer
{
    public function render(DOMElement $node, $container): void
    {
        // Get inline style
        $inlineStyle = $node->hasAttribute('style') ? $node->getAttribute('style') : null;
        $cssStyle = $this->cssParser->getStyleForElement('table', $inlineStyle);

        // Convert to PhpWord table style
        $tableStyle = $this->cssParser->convertToTableStyle($cssStyle);

        // Set defaults
        if (! isset($tableStyle['width'])) {
            $tableStyle['width'] = 100 * 50;
            $tableStyle['unit'] = TblWidth::PERCENT;
        }

        $table = $container->addTable($tableStyle);

        $xpath = new \DOMXPath($node->ownerDocument);

        // Process thead
        $theadRows = $xpath->query('.//thead/tr', $node);
        foreach ($theadRows as $row) {
            $this->addTableRow($row, $table, true);
        }

        // Process tbody
        $tbodyRows = $xpath->query('.//tbody/tr', $node);
        foreach ($tbodyRows as $row) {
            $this->addTableRow($row, $table, false);
        }

        // Process direct tr (if no thead/tbody)
        if ($theadRows->length === 0 && $tbodyRows->length === 0) {
            $rows = $xpath->query('.//tr', $node);
            foreach ($rows as $row) {
                $this->addTableRow($row, $table, false);
            }
        }
    }

    /**
     * Add table row with cells
     */
    protected function addTableRow(DOMElement $rowNode, $table, bool $isHeader): void
    {
        $table->addRow();

        $xpath = new \DOMXPath($rowNode->ownerDocument);
        $cells = $xpath->query('.//td | .//th', $rowNode);

        foreach ($cells as $cell) {
            $this->addTableCell($cell, $table, $isHeader);
        }
    }

    /**
     * Add table cell with content
     */
    protected function addTableCell(DOMElement $cellNode, $table, bool $isHeader): void
    {
        // Get inline style
        $inlineStyle = $cellNode->hasAttribute('style') ? $cellNode->getAttribute('style') : null;
        $tag = strtolower($cellNode->nodeName);
        $cssStyle = $this->cssParser->getStyleForElement($tag, $inlineStyle);

        // Convert to PhpWord cell style
        $cellStyle = $this->cssParser->convertToCellStyle($cssStyle);

        // Set default border
        if (! isset($cellStyle['borderTopSize'])) {
            $cellStyle['borderTopSize'] = 6;
            $cellStyle['borderBottomSize'] = 6;
            $cellStyle['borderLeftSize'] = 6;
            $cellStyle['borderRightSize'] = 6;
        }
        if (! isset($cellStyle['borderTopColor'])) {
            $cellStyle['borderTopColor'] = 'DDDDDD';
            $cellStyle['borderBottomColor'] = 'DDDDDD';
            $cellStyle['borderLeftColor'] = 'DDDDDD';
            $cellStyle['borderRightColor'] = 'DDDDDD';
        }

        // Set header background
        if ($isHeader && ! isset($cellStyle['bgColor'])) {
            $cellStyle['bgColor'] = 'F2F2F2';
        }

        // Handle colspan
        if ($cellNode->hasAttribute('colspan')) {
            $cellStyle['gridSpan'] = (int) $cellNode->getAttribute('colspan');
        }

        // Handle rowspan
        if ($cellNode->hasAttribute('rowspan')) {
            $rowspan = (int) $cellNode->getAttribute('rowspan');
            if ($rowspan > 1) {
                $cellStyle['vMerge'] = 'restart';
            }
        }

        $tableCell = $table->addCell(null, $cellStyle);

        // Get font style
        $fontStyle = $this->cssParser->convertToFontStyle($cssStyle);
        if (! isset($fontStyle['size'])) {
            $fontStyle['size'] = 11;
        }
        if ($isHeader && ! isset($fontStyle['bold'])) {
            $fontStyle['bold'] = true;
        }

        // Add content
        $this->addCellContent($cellNode, $tableCell, $fontStyle);
    }

    /**
     * Add content to cell
     */
    protected function addCellContent(DOMElement $cellNode, $tableCell, array $baseFontStyle): void
    {
        foreach ($cellNode->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim($child->textContent);
                if (! empty($text)) {
                    $tableCell->addText($text, $baseFontStyle);
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($child->nodeName);

                // Handle paragraph in cell
                if ($tag === 'p') {
                    $textRun = $tableCell->addTextRun();
                    $this->addInlineElements($child, $textRun, $baseFontStyle);
                }
                // Handle other inline elements
                else {
                    $textRun = $tableCell->addTextRun();
                    $this->addInlineElements($child, $textRun, $baseFontStyle);
                }
            }
        }
    }
}
