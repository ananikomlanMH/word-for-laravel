<?php

namespace WordForLaravel\Renderers\Elements;

use DOMElement;

/**
 * Page Number Renderer
 * Handles <page-number> elements to insert dynamic page numbers
 */
class PageNumberRenderer extends BaseElementRenderer
{
    /**
     * Supported page number variables
     */
    protected array $supportedVariables = [
        '{PAGE}',
        '{NUMPAGES}',
        '{TOTALPAGES}',
        '{SECTIONPAGES}',
    ];

    /**
     * Render page number element
     */
    public function render(DOMElement $node, $container): void
    {
        // Get format attribute (default: {PAGE})
        $format = $node->getAttribute('format') ?: '{PAGE}';

        // Get restart attribute
        $restart = $node->hasAttribute('restart')
            ? filter_var($node->getAttribute('restart'), FILTER_VALIDATE_BOOLEAN)
            : false;

        // Get computed style
        $cssStyle = $this->getComputedStyle($node);

        // Convert to font style
        $fontStyle = $this->cssParser->convertToFontStyle($cssStyle);

        // Set defaults
        if (! isset($fontStyle['size'])) {
            $fontStyle['size'] = 11;
        }
        if (! isset($fontStyle['name'])) {
            $fontStyle['name'] = 'Arial';
        }

        // Convert the format string to PhpWord format
        $phpWordFormat = $this->convertFormat($format);

        // Validate format
        if (! $this->isValidFormat($phpWordFormat)) {
            // Fallback to simple page number
            $phpWordFormat = '{PAGE}';
        }

        // Add the preserve text (page number)
        $container->addPreserveText($phpWordFormat, $fontStyle);
    }

    /**
     * Convert custom format to PhpWord format
     * Supports: {PAGE}, {NUMPAGES}, {TOTALPAGES}, {SECTIONPAGES}
     */
    protected function convertFormat(string $format): string
    {
        // Replace common placeholders (case-insensitive)
        $replacements = [
            // Standard uppercase
            '{PAGE}' => '{PAGE}',
            '{NUMPAGES}' => '{NUMPAGES}',
            '{SECTIONPAGES}' => '{SECTIONPAGES}',

            // Aliases
            '{TOTALPAGES}' => '{NUMPAGES}',

            // Lowercase variants
            '{page}' => '{PAGE}',
            '{numpages}' => '{NUMPAGES}',
            '{totalpages}' => '{NUMPAGES}',
            '{sectionpages}' => '{SECTIONPAGES}',

            // Mixed case variants
            '{Page}' => '{PAGE}',
            '{NumPages}' => '{NUMPAGES}',
            '{TotalPages}' => '{NUMPAGES}',
            '{SectionPages}' => '{SECTIONPAGES}',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }

    /**
     * Validate that format contains at least one supported variable
     */
    protected function isValidFormat(string $format): bool
    {
        foreach ($this->supportedVariables as $variable) {
            if (strpos($format, $variable) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available page number variables
     */
    public function getSupportedVariables(): array
    {
        return $this->supportedVariables;
    }

    /**
     * Get example formats
     */
    public function getExampleFormats(): array
    {
        return [
            '{PAGE}' => 'Simple page number: 1, 2, 3...',
            'Page {PAGE}' => 'With prefix: Page 1, Page 2...',
            '{PAGE} / {NUMPAGES}' => 'Page X of Y: 1 / 10, 2 / 10...',
            'Page {PAGE} sur {NUMPAGES}' => 'French format: Page 1 sur 10...',
            'Page {PAGE} of {NUMPAGES}' => 'English format: Page 1 of 10...',
            'Página {PAGE} de {NUMPAGES}' => 'Spanish format: Página 1 de 10...',
            '{PAGE}-{NUMPAGES}' => 'With separator: 1-10, 2-10...',
        ];
    }

    /**
     * Create a common footer format
     */
    public static function createCommonFooterFormat(
        string $format = 'Page {PAGE} sur {NUMPAGES}',
        string $alignment = 'center',
        array $additionalStyle = []
    ): string {
        $style = array_merge([
            'text-align' => $alignment,
            'font-size' => '10pt',
            'color' => '#666',
        ], $additionalStyle);

        $styleString = '';
        foreach ($style as $property => $value) {
            $styleString .= "{$property}: {$value}; ";
        }

        return sprintf(
            '<p style="%s"><page-number format="%s" /></p>',
            trim($styleString),
            htmlspecialchars($format)
        );
    }

    /**
     * Create a common header format
     */
    public static function createCommonHeaderFormat(
        string $title,
        string $pageFormat = '{PAGE}',
        array $titleStyle = [],
        array $pageStyle = []
    ): string {
        $defaultTitleStyle = [
            'font-weight' => 'bold',
            'font-size' => '14pt',
            'color' => '#2c3e50',
        ];

        $defaultPageStyle = [
            'font-size' => '10pt',
            'color' => '#7f8c8d',
        ];

        $titleStyle = array_merge($defaultTitleStyle, $titleStyle);
        $pageStyle = array_merge($defaultPageStyle, $pageStyle);

        $titleStyleString = '';
        foreach ($titleStyle as $property => $value) {
            $titleStyleString .= "{$property}: {$value}; ";
        }

        $pageStyleString = '';
        foreach ($pageStyle as $property => $value) {
            $pageStyleString .= "{$property}: {$value}; ";
        }

        return sprintf(
            '<table style="width: 100%%;">
                <tr>
                    <td style="width: 70%%; %s">%s</td>
                    <td style="width: 30%%; text-align: right; %s">
                        <page-number format="%s" />
                    </td>
                </tr>
            </table>',
            trim($titleStyleString),
            htmlspecialchars($title),
            trim($pageStyleString),
            htmlspecialchars($pageFormat)
        );
    }
}
