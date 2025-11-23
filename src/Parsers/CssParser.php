<?php

namespace WordForLaravel\Parsers;

class CssParser
{
    protected array $styles = [];

    protected array $propertyMap = [
        // Font properties
        'font-size' => 'size',
        'color' => 'color',
        'font-family' => 'name',
        'font-weight' => 'bold',
        'font-style' => 'italic',
        'text-decoration' => 'underline',
        'background-color' => 'bgColor',
        'text-transform' => 'allCaps',

        // Paragraph properties
        'text-align' => 'alignment',
        'line-height' => 'lineHeight',
        'margin-left' => 'indent',
        'margin-top' => 'spaceBefore',
        'margin-bottom' => 'spaceAfter',
        'text-indent' => 'firstLine',

        // Table/Cell properties
        'border-color' => 'borderColor',
        'border-width' => 'borderSize',
        'vertical-align' => 'valign',
        'width' => 'width',
    ];

    /**
     * Parse CSS from HTML
     */
    public function parse(string $css): void
    {
        // Remove comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);

        // Match CSS rules
        preg_match_all('/([^{]+)\{([^}]+)}/s', $css, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $selectors = array_map('trim', explode(',', $match[1]));
            $properties = $this->parseProperties($match[2]);

            foreach ($selectors as $selector) {
                // Merge with existing styles
                if (isset($this->styles[$selector])) {
                    $this->styles[$selector] = array_merge($this->styles[$selector], $properties);
                } else {
                    $this->styles[$selector] = $properties;
                }
            }
        }
    }

    /**
     * Parse CSS properties
     */
    protected function parseProperties(string $propertiesString): array
    {
        $properties = [];
        $declarations = explode(';', $propertiesString);

        foreach ($declarations as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) === 2) {
                $property = trim($parts[0]);
                $value = trim($parts[1]);
                $properties[$property] = $value;
            }
        }

        return $properties;
    }

    /**
     * Parse inline style attribute
     */
    public function parseInlineStyle(string $styleAttr): array
    {
        return $this->parseProperties($styleAttr);
    }

    /**
     * Get style for element with inline style override
     */
    public function getStyleForElement(string $tag, ?string $inlineStyle = null): array
    {
        $baseStyle = $this->styles[$tag] ?? [];

        if ($inlineStyle) {
            $inlineStyles = $this->parseInlineStyle($inlineStyle);
            $baseStyle = array_merge($baseStyle, $inlineStyles);
        }

        return $baseStyle;
    }

    /**
     * Convert CSS styles to PhpWord font style
     */
    public function convertToFontStyle(array $cssStyles): array
    {
        $fontStyle = [];

        foreach ($cssStyles as $property => $value) {
            switch ($property) {
                case 'font-size':
                    $fontStyle['size'] = $this->convertFontSize($value);
                    break;
                case 'color':
                    $fontStyle['color'] = $this->convertColor($value);
                    break;
                case 'font-family':
                    $fontStyle['name'] = $this->getFontFamily($value);
                    break;
                case 'font-weight':
                    if (in_array($value, ['bold', 'bolder', '700', '800', '900'])) {
                        $fontStyle['bold'] = true;
                    }
                    break;
                case 'font-style':
                    if ($value === 'italic' || $value === 'oblique') {
                        $fontStyle['italic'] = true;
                    }
                    break;
                case 'text-decoration':
                    if (strpos($value, 'underline') !== false) {
                        $fontStyle['underline'] = 'single';
                    } elseif (strpos($value, 'line-through') !== false) {
                        $fontStyle['strikethrough'] = true;
                    }
                    break;
                case 'background-color':
                case 'background':
                    $fontStyle['bgColor'] = $this->convertColor($value);
                    break;
                case 'text-transform':
                    if ($value === 'uppercase') {
                        $fontStyle['allCaps'] = true;
                    } elseif ($value === 'capitalize') {
                        $fontStyle['smallCaps'] = true;
                    }
                    break;
                case 'vertical-align':
                    if ($value === 'super' || $value === 'superscript') {
                        $fontStyle['superScript'] = true;
                    } elseif ($value === 'sub' || $value === 'subscript') {
                        $fontStyle['subScript'] = true;
                    }
                    break;
            }
        }

        return $fontStyle;
    }

    /**
     * Convert CSS styles to PhpWord paragraph style
     */
    public function convertToParagraphStyle(array $cssStyles): array
    {
        $paragraphStyle = [];

        foreach ($cssStyles as $property => $value) {
            switch ($property) {
                case 'text-align':
                    $alignmentMap = [
                        'left' => 'start',
                        'center' => 'center',
                        'right' => 'end',
                        'justify' => 'both',
                    ];
                    $paragraphStyle['alignment'] = $alignmentMap[$value] ?? $value;
                    break;
                case 'line-height':
                    // Convert line-height to spacing
                    if (is_numeric($value)) {
                        $paragraphStyle['lineHeight'] = (float) $value;
                    } elseif (strpos($value, 'pt') !== false) {
                        $points = (int) str_replace('pt', '', $value);
                        $paragraphStyle['spacing'] = $points * 20; // Convert to twips
                    }
                    break;
                case 'margin-left':
                case 'padding-left':
                    $paragraphStyle['indent'] = $this->convertToTwips($value);
                    break;
                case 'margin-top':
                    $paragraphStyle['spaceBefore'] = $this->convertToTwips($value);
                    break;
                case 'margin-bottom':
                    $paragraphStyle['spaceAfter'] = $this->convertToTwips($value);
                    break;
                case 'text-indent':
                    $paragraphStyle['indentation'] = [
                        'firstLine' => $this->convertToTwips($value),
                    ];
                    break;
            }
        }

        return $paragraphStyle;
    }

    /**
     * Convert CSS styles to PhpWord table style
     */
    public function convertToTableStyle(array $cssStyles): array
    {
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
        ];

        foreach ($cssStyles as $property => $value) {
            switch ($property) {
                case 'border-color':
                    $tableStyle['borderColor'] = $this->convertColor($value);
                    $tableStyle['borderTopColor'] = $tableStyle['borderColor'];
                    $tableStyle['borderBottomColor'] = $tableStyle['borderColor'];
                    $tableStyle['borderLeftColor'] = $tableStyle['borderColor'];
                    $tableStyle['borderRightColor'] = $tableStyle['borderColor'];
                    break;
                case 'border-width':
                    $tableStyle['borderSize'] = $this->convertBorderSize($value);
                    $tableStyle['borderTopSize'] = $tableStyle['borderSize'];
                    $tableStyle['borderBottomSize'] = $tableStyle['borderSize'];
                    $tableStyle['borderLeftSize'] = $tableStyle['borderSize'];
                    $tableStyle['borderRightSize'] = $tableStyle['borderSize'];
                    break;
                case 'border-top-color':
                    $tableStyle['borderTopColor'] = $this->convertColor($value);
                    break;
                case 'border-bottom-color':
                    $tableStyle['borderBottomColor'] = $this->convertColor($value);
                    break;
                case 'border-left-color':
                    $tableStyle['borderLeftColor'] = $this->convertColor($value);
                    break;
                case 'border-right-color':
                    $tableStyle['borderRightColor'] = $this->convertColor($value);
                    break;
                case 'background-color':
                    $tableStyle['bgColor'] = $this->convertColor($value);
                    break;
                case 'width':
                    if (strpos($value, '%') !== false) {
                        $tableStyle['width'] = (int) str_replace('%', '', $value) * 50;
                        $tableStyle['unit'] = \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT;
                    }
                    break;
            }
        }

        return $tableStyle;
    }

    /**
     * Convert CSS styles to PhpWord cell style
     */
    public function convertToCellStyle(array $cssStyles): array
    {
        $cellStyle = [];

        foreach ($cssStyles as $property => $value) {
            switch ($property) {
                case 'background-color':
                    $cellStyle['bgColor'] = $this->convertColor($value);
                    break;
                case 'border-color':
                    $color = $this->convertColor($value);
                    $cellStyle['borderTopColor'] = $color;
                    $cellStyle['borderBottomColor'] = $color;
                    $cellStyle['borderLeftColor'] = $color;
                    $cellStyle['borderRightColor'] = $color;
                    break;
                case 'border-width':
                    $size = $this->convertBorderSize($value);
                    $cellStyle['borderTopSize'] = $size;
                    $cellStyle['borderBottomSize'] = $size;
                    $cellStyle['borderLeftSize'] = $size;
                    $cellStyle['borderRightSize'] = $size;
                    break;
                case 'vertical-align':
                    $valignMap = [
                        'top' => 'top',
                        'middle' => 'center',
                        'center' => 'center',
                        'bottom' => 'bottom',
                    ];
                    $cellStyle['valign'] = $valignMap[$value] ?? 'top';
                    break;
                case 'padding':
                    $padding = $this->convertToTwips($value);
                    $cellStyle['cellMarginTop'] = $padding;
                    $cellStyle['cellMarginBottom'] = $padding;
                    $cellStyle['cellMarginLeft'] = $padding;
                    $cellStyle['cellMarginRight'] = $padding;
                    break;
            }
        }

        return $cellStyle;
    }

    /**
     * Convert CSS font size to Word points
     */
    public function convertFontSize(string $size): int
    {
        $size = strtolower(trim($size));

        if (strpos($size, 'pt') !== false) {
            return (int) str_replace('pt', '', $size);
        }
        if (strpos($size, 'px') !== false) {
            return (int) (str_replace('px', '', $size) * 0.75);
        }
        if (strpos($size, 'em') !== false) {
            return (int) (str_replace('em', '', $size) * 12);
        }

        // Named sizes
        $namedSizes = [
            'xx-small' => 8,
            'x-small' => 9,
            'small' => 10,
            'medium' => 12,
            'large' => 14,
            'x-large' => 18,
            'xx-large' => 24,
        ];

        return $namedSizes[$size] ?? 12;
    }

    /**
     * Convert CSS color to Word color
     */
    public function convertColor(string $color): string
    {
        $color = strtolower(trim($color));

        // Hex color
        if (strpos($color, '#') === 0) {
            $hex = ltrim($color, '#');
            // Convert 3-digit hex to 6-digit
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }

            return strtoupper($hex);
        }

        // RGB color
        if (strpos($color, 'rgb') === 0) {
            preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)/', $color, $matches);
            if (count($matches) >= 4) {
                return sprintf('%02X%02X%02X', $matches[1], $matches[2], $matches[3]);
            }
        }

        // Named colors
        $namedColors = [
            'black' => '000000',
            'white' => 'FFFFFF',
            'red' => 'FF0000',
            'green' => '008000',
            'blue' => '0000FF',
            'yellow' => 'FFFF00',
            'cyan' => '00FFFF',
            'magenta' => 'FF00FF',
            'gray' => '808080',
            'grey' => '808080',
            'silver' => 'C0C0C0',
            'maroon' => '800000',
            'olive' => '808000',
            'lime' => '00FF00',
            'aqua' => '00FFFF',
            'teal' => '008080',
            'navy' => '000080',
            'fuchsia' => 'FF00FF',
            'purple' => '800080',
        ];

        return $namedColors[$color] ?? '000000';
    }

    /**
     * Get font family name
     */
    public function getFontFamily(string $fontFamily): string
    {
        $fonts = explode(',', $fontFamily);

        return trim(str_replace(['\'', '"'], '', $fonts[0]));
    }

    /**
     * Convert CSS measurement to twips (1/20 of a point)
     */
    protected function convertToTwips(string $value): int
    {
        $value = strtolower(trim($value));

        if (strpos($value, 'pt') !== false) {
            return (int) (str_replace('pt', '', $value) * 20);
        }
        if (strpos($value, 'px') !== false) {
            return (int) (str_replace('px', '', $value) * 15);
        }
        if (strpos($value, 'in') !== false) {
            return (int) (str_replace('in', '', $value) * 1440);
        }
        if (strpos($value, 'cm') !== false) {
            return (int) (str_replace('cm', '', $value) * 567);
        }

        return 0;
    }

    /**
     * Convert CSS border size
     */
    protected function convertBorderSize(string $size): int
    {
        $size = strtolower(trim($size));

        if (strpos($size, 'px') !== false) {
            return (int) (str_replace('px', '', $size) * 8);
        }
        if (strpos($size, 'pt') !== false) {
            return (int) (str_replace('pt', '', $size) * 8);
        }

        // Named sizes
        $namedSizes = [
            'thin' => 4,
            'medium' => 6,
            'thick' => 8,
        ];

        return $namedSizes[$size] ?? 6;
    }

    /**
     * Reset styles
     */
    public function reset(): void
    {
        $this->styles = [];
    }
}
