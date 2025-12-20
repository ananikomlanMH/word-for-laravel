<?php

namespace WordForLaravel\Renderers\Elements;

use DOMElement;
use PhpOffice\PhpWord\Style\ListItem;

/**
 * List Renderer
 */
class ListRenderer extends BaseElementRenderer
{
    public function render(DOMElement $node, $container, string $type, int $depth = 0): void
    {
        $xpath = new \DOMXPath($node->ownerDocument);
        $items = $xpath->query('./li', $node);

        // Get inline style
        $inlineStyle = $node->hasAttribute('style') ? $node->getAttribute('style') : null;
        $listCssStyle = $this->cssParser->getStyleForElement($type, $inlineStyle);

        $listStyle = $this->getListStyleType($type, $listCssStyle);
        $fontStyle = $this->cssParser->convertToFontStyle($listCssStyle);

        // Set default font if not specified
        if (! isset($fontStyle['size'])) {
            $fontStyle['size'] = 12;
        }

        if (! isset($fontStyle['name'])) {
            $fontStyle['name'] = 'Arial';
        }

        foreach ($items as $item) {
            $this->renderListItem($item, $container, $depth, $listStyle, $fontStyle, $xpath);
        }
    }

    /**
     * Render a single list item
     */
    protected function renderListItem(
        DOMElement $item,
        $container,
        int $depth,
        string $listStyle,
        array $fontStyle,
        \DOMXPath $xpath
    ): void {
        // Get inline style for this item
        $itemInlineStyle = $item->hasAttribute('style') ? $item->getAttribute('style') : null;
        $itemCssStyle = $this->cssParser->getStyleForElement('li', $itemInlineStyle);
        $itemFontStyle = array_merge($fontStyle, $this->cssParser->convertToFontStyle($itemCssStyle));

        // Check for nested lists
        $xpath->query('./ul | ./ol', $item);

        // Check if item has complex content (formatted text)
        $this->hasComplexContent($item);

        // Use ListItemRun for complex content
        $listItemRun = $container->addListItemRun($depth, null, $listStyle);

        // Process child nodes
        foreach ($item->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim($child->textContent);
                if ($text !== '' && $text !== '0') {
                    $listItemRun->addText($text, $itemFontStyle);
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $childTag = strtolower($child->nodeName);

                // Handle nested lists
                if ($childTag === 'ul' || $childTag === 'ol') {
                    // First close the current list item
                    $this->render($child, $container, $childTag, $depth + 1);
                } else {
                    // Handle inline formatting
                    $this->addInlineElements($child, $listItemRun, $itemFontStyle);
                }
            }
        }

    }

    /**
     * Get list style type from CSS or default
     */
    protected function getListStyleType(string $type, array $cssStyle): string
    {
        $listStyleType = $cssStyle['list-style-type'] ?? '';

        $styleMap = [
            'decimal' => ListItem::TYPE_NUMBER,
            'decimal-leading-zero' => ListItem::TYPE_NUMBER,
            'upper-alpha' => ListItem::TYPE_ALPHANUM,
            'upper-latin' => ListItem::TYPE_ALPHANUM,
            'lower-alpha' => ListItem::TYPE_ALPHANUM,
            'lower-latin' => ListItem::TYPE_ALPHANUM,
            'disc' => ListItem::TYPE_BULLET_FILLED,
            'circle' => ListItem::TYPE_BULLET_EMPTY,
            'square' => ListItem::TYPE_SQUARE_FILLED,
            'none' => ListItem::TYPE_BULLET_EMPTY,
        ];

        if (isset($styleMap[$listStyleType])) {
            return $styleMap[$listStyleType];
        }

        // Default based on tag type
        return $type === 'ol' ? ListItem::TYPE_NUMBER : ListItem::TYPE_BULLET_FILLED;
    }

    /**
     * Check if list item has complex content requiring ListItemRun
     */
    protected function hasComplexContent(DOMElement $node): bool
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($child->nodeName);
                // Check for formatting tags
                if (in_array($tag, ['strong', 'b', 'em', 'i', 'u', 'span', 'a', 'code', 'mark', 'small', 'sub', 'sup'])) {
                    return true;
                }
            }
        }

        return false;
    }
}
