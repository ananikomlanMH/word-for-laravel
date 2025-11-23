<?php

namespace WordForLaravel\Renderers\Elements;

use DOMElement;

/**
 * Paragraph Renderer
 */
class ParagraphRenderer extends BaseElementRenderer
{
    public function render(DOMElement $node, $container): void
    {
        // Get inline style
        $inlineStyle = $node->hasAttribute('style') ? $node->getAttribute('style') : null;
        $cssStyle = $this->cssParser->getStyleForElement('p', $inlineStyle);

        // Convert to PhpWord styles
        $fontStyle = $this->cssParser->convertToFontStyle($cssStyle);
        $paragraphStyle = $this->cssParser->convertToParagraphStyle($cssStyle);

        // Set defaults
        if (! isset($fontStyle['size'])) {
            $fontStyle['size'] = 12;
        }
        if (! isset($fontStyle['name'])) {
            $fontStyle['name'] = 'Arial';
        }

        // Create text run
        $textRun = $container->addTextRun($paragraphStyle);

        // Add inline elements with formatting
        $this->addInlineElements($node, $textRun, $fontStyle);
    }
}
