<?php

namespace WordForLaravel\Renderers\Elements;

use DOMElement;
use PhpOffice\PhpWord\SimpleType\TblWidth;

/**
 * Table Renderer
 */
class TableRenderer extends BaseElementRenderer
{
    /**
     * Track cells with rowspan for vMerge continuation
     * Structure: [rowIndex][colIndex] => remainingRows
     */
    protected array $rowspanTracker = [];

    public function render(DOMElement $node, $container): void
    {
        // Reset rowspan tracker for this table
        $this->rowspanTracker = [];

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

        $rowIndex = 0;

        // Process thead
        $theadRows = $xpath->query('.//thead/tr', $node);
        foreach ($theadRows as $row) {
            $this->addTableRow($row, $table, true, $rowIndex);
            $rowIndex++;
        }

        // Process tbody
        $tbodyRows = $xpath->query('.//tbody/tr', $node);
        foreach ($tbodyRows as $row) {
            $this->addTableRow($row, $table, false, $rowIndex);
            $rowIndex++;
        }

        // Process direct tr (if no thead/tbody)
        if ($theadRows->length === 0 && $tbodyRows->length === 0) {
            $rows = $xpath->query('.//tr', $node);
            foreach ($rows as $row) {
                $this->addTableRow($row, $table, false, $rowIndex);
                $rowIndex++;
            }
        }
    }

    /**
     * Add table row with cells
     */
    protected function addTableRow(DOMElement $rowNode, $table, bool $isHeader, int $rowIndex): void
    {
        $table->addRow();

        $xpath = new \DOMXPath($rowNode->ownerDocument);
        $cells = $xpath->query('.//td | .//th', $rowNode);

        $colIndex = 0;

        foreach ($cells as $cell) {
            // Skip columns that are part of a previous rowspan
            while (isset($this->rowspanTracker[$rowIndex][$colIndex]) &&
                   $this->rowspanTracker[$rowIndex][$colIndex] > 0) {

                // Add a continuing vMerge cell
                $this->addContinuingCell($table);

                // Decrement the rowspan counter
                $this->rowspanTracker[$rowIndex][$colIndex]--;

                // Copy the rowspan info to the next row if needed
                if ($this->rowspanTracker[$rowIndex][$colIndex] > 0) {
                    if (! isset($this->rowspanTracker[$rowIndex + 1])) {
                        $this->rowspanTracker[$rowIndex + 1] = [];
                    }
                    $this->rowspanTracker[$rowIndex + 1][$colIndex] =
                        $this->rowspanTracker[$rowIndex][$colIndex];
                }

                $colIndex++;
            }

            $this->addTableCell($cell, $table, $isHeader, $rowIndex, $colIndex);

            // Handle colspan - skip the next columns
            $colspan = 1;
            if ($cell->hasAttribute('colspan')) {
                $colspan = (int) $cell->getAttribute('colspan');
            }

            $colIndex += $colspan;
        }

        // After processing all visible cells, check for remaining continuation cells
        // This handles rowspans that extend beyond the last visible cell in the row
        if (isset($this->rowspanTracker[$rowIndex])) {
            // Get all column indices that need continuation cells
            $remainingCols = array_keys($this->rowspanTracker[$rowIndex]);
            sort($remainingCols);

            foreach ($remainingCols as $col) {
                if ($col >= $colIndex && $this->rowspanTracker[$rowIndex][$col] > 0) {
                    // Add the continuing cell
                    $this->addContinuingCell($table);

                    // Decrement the counter
                    $this->rowspanTracker[$rowIndex][$col]--;

                    // Propagate to next row if needed
                    if ($this->rowspanTracker[$rowIndex][$col] > 0) {
                        if (! isset($this->rowspanTracker[$rowIndex + 1])) {
                            $this->rowspanTracker[$rowIndex + 1] = [];
                        }
                        $this->rowspanTracker[$rowIndex + 1][$col] =
                            $this->rowspanTracker[$rowIndex][$col];
                    }
                }
            }
        }
    }

    /**
     * Add a continuing cell for rowspan (vMerge continue)
     */
    protected function addContinuingCell($table): void
    {
        $cellStyle = [
            'vMerge' => 'continue',
            'borderTopSize' => 6,
            'borderBottomSize' => 6,
            'borderLeftSize' => 6,
            'borderRightSize' => 6,
            'borderTopColor' => 'DDDDDD',
            'borderBottomColor' => 'DDDDDD',
            'borderLeftColor' => 'DDDDDD',
            'borderRightColor' => 'DDDDDD',
        ];

        $table->addCell(null, $cellStyle);
    }

    /**
     * Add table cell with content
     */
    protected function addTableCell(DOMElement $cellNode, $table, bool $isHeader, int $rowIndex, int $colIndex): void
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

        // Handle colspan (gridSpan)
        $colspan = 1;
        if ($cellNode->hasAttribute('colspan')) {
            $colspan = (int) $cellNode->getAttribute('colspan');
            if ($colspan > 1) {
                $cellStyle['gridSpan'] = $colspan;
            }
        }

        // Handle rowspan (vMerge)
        if ($cellNode->hasAttribute('rowspan')) {
            $rowspan = (int) $cellNode->getAttribute('rowspan');
            if ($rowspan > 1) {
                // This is the starting cell - use 'restart'
                $cellStyle['vMerge'] = 'restart';

                // Track this rowspan for the following rows
                // We need to add (rowspan - 1) continuing cells in the next rows
                $remainingRows = $rowspan - 1;

                // For each column this cell spans (due to colspan)
                for ($c = 0; $c < $colspan; $c++) {
                    $currentCol = $colIndex + $c;

                    // Mark the next rows for this column
                    for ($r = 1; $r <= $remainingRows; $r++) {
                        $nextRowIndex = $rowIndex + $r;
                        if (! isset($this->rowspanTracker[$nextRowIndex])) {
                            $this->rowspanTracker[$nextRowIndex] = [];
                        }
                        $this->rowspanTracker[$nextRowIndex][$currentCol] = $remainingRows - $r + 1;
                    }
                }
            }
        }

        // Get cell width from HTML attribute or CSS
        $cellWidth = null;
        if ($cellNode->hasAttribute('width')) {
            $cellWidth = $this->convertWidthToTwips($cellNode->getAttribute('width'));
        } elseif (isset($cssStyle['width'])) {
            $cellWidth = $this->convertWidthToTwips($cssStyle['width']);
        }

        $tableCell = $table->addCell($cellWidth, $cellStyle);

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
     * Convert width value to twips
     */
    protected function convertWidthToTwips(string $width): ?int
    {
        $width = strtolower(trim($width));

        // Percentage - return null to use default
        if (strpos($width, '%') !== false) {
            return null;
        }

        // Pixels to twips (1px = 15 twips)
        if (strpos($width, 'px') !== false) {
            return (int) (str_replace('px', '', $width) * 15);
        }

        // Points to twips (1pt = 20 twips)
        if (strpos($width, 'pt') !== false) {
            return (int) (str_replace('pt', '', $width) * 20);
        }

        // Inches to twips (1in = 1440 twips)
        if (strpos($width, 'in') !== false) {
            return (int) (str_replace('in', '', $width) * 1440);
        }

        // Centimeters to twips (1cm = 567 twips)
        if (strpos($width, 'cm') !== false) {
            return (int) (str_replace('cm', '', $width) * 567);
        }

        // No unit - assume pixels
        if (is_numeric($width)) {
            return (int) ($width * 15);
        }

        return null;
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
