<?php

namespace WordForLaravel\Renderers\Elements;

use DOMElement;

/**
 * Heading Renderer
 */
class HeadingRenderer extends BaseElementRenderer
{
    protected array $defaultSizes = [
        'h1' => 24,
        'h2' => 18,
        'h3' => 16,
        'h4' => 14,
        'h5' => 12,
        'h6' => 10,
    ];

    public function render(DOMElement $node, $container, string $tag): void
    {
        // Get inline style
        $inlineStyle = $node->hasAttribute('style') ? $node->getAttribute('style') : null;
        $cssStyle = $this->cssParser->getStyleForElement($tag, $inlineStyle);

        // Convert to PhpWord styles
        $fontStyle = $this->cssParser->convertToFontStyle($cssStyle);
        $paragraphStyle = $this->cssParser->convertToParagraphStyle($cssStyle);

        // Set defaults for headings
        if (! isset($fontStyle['size'])) {
            $fontStyle['size'] = $this->defaultSizes[$tag] ?? 12;
        }
        if (! isset($fontStyle['bold'])) {
            $fontStyle['bold'] = true;
        }
        if (! isset($fontStyle['name'])) {
            $fontStyle['name'] = 'Arial';
        }

        // Check if heading has complex content
        if ($this->hasInlineFormatting($node)) {
            $textRun = $container->addTextRun($paragraphStyle);
            $this->addInlineElements($node, $textRun, $fontStyle);
        } else {
            $text = $this->getTextContent($node);
            $container->addText($text, $fontStyle, $paragraphStyle);
        }
    }

    /**
     * Check if node has inline formatting elements
     */
    protected function hasInlineFormatting(DOMElement $node): bool
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                return true;
            }
        }

        return false;
    }
}
