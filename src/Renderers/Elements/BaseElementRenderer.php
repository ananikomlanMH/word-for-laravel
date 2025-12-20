<?php

namespace WordForLaravel\Renderers\Elements;

use DOMElement;
use WordForLaravel\Parsers\CssParser;

/**
 * Base Element Renderer
 */
abstract class BaseElementRenderer
{
    public function __construct(
        protected CssParser $cssParser
    ) {}

    /**
     * Get text content from node
     */
    protected function getTextContent(DOMElement $node): string
    {
        return trim($node->textContent);
    }

    /**
     * Add inline elements with formatting (with CSS support)
     */
    protected function addInlineElements(DOMElement $node, $textRun, array $baseStyle): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->textContent;
                if (! in_array(trim($text), ['', '0'], true)) {
                    $textRun->addText($text, $baseStyle);
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($child->nodeName);

                // Get inline style for this element
                $inlineStyle = $child->hasAttribute('style') ? $child->getAttribute('style') : null;
                $cssStyle = $this->cssParser->getStyleForElement($tag, $inlineStyle);

                // Convert CSS to font style
                $elementFontStyle = $this->cssParser->convertToFontStyle($cssStyle);

                // Merge with base style (inline style has priority)
                $style = array_merge($baseStyle, $elementFontStyle);

                // Apply tag-specific styles
                switch ($tag) {
                    case 'strong':
                    case 'b':
                        $style['bold'] = true;
                        break;
                    case 'em':
                    case 'i':
                        $style['italic'] = true;
                        break;
                    case 'u':
                        $style['underline'] = 'single';
                        break;
                    case 'del':
                    case 's':
                    case 'strike':
                        $style['strikethrough'] = true;
                        break;
                    case 'mark':
                        if (! isset($style['bgColor'])) {
                            $style['bgColor'] = 'FFFF00'; // Yellow
                        }

                        break;
                    case 'code':
                        if (! isset($style['name'])) {
                            $style['name'] = 'Courier New';
                        }

                        if (! isset($style['bgColor'])) {
                            $style['bgColor'] = 'F5F5F5';
                        }

                        break;
                    case 'small':
                        if (! isset($style['size'])) {
                            $style['size'] = max(8, ($baseStyle['size'] ?? 12) - 2);
                        }

                        break;
                    case 'sub':
                        $style['subScript'] = true;
                        if (! isset($style['size'])) {
                            $style['size'] = max(8, ($baseStyle['size'] ?? 12) - 2);
                        }

                        break;
                    case 'sup':
                        $style['superScript'] = true;
                        if (! isset($style['size'])) {
                            $style['size'] = max(8, ($baseStyle['size'] ?? 12) - 2);
                        }

                        break;
                    case 'a':
                        // Links
                        if (! isset($style['color'])) {
                            $style['color'] = '0000FF';
                        }

                        if (! isset($style['underline'])) {
                            $style['underline'] = 'single';
                        }

                        break;
                }

                // Process child nodes recursively
                if ($child->hasChildNodes()) {
                    $this->addInlineElements($child, $textRun, $style);
                } else {
                    $text = $child->textContent;
                    if (! in_array(trim($text), ['', '0'], true)) {
                        $textRun->addText($text, $style);
                    }
                }
            }
        }
    }

    /**
     * Extract class names from element
     */
    protected function getClassNames(DOMElement $element): array
    {
        if ($element->hasAttribute('class')) {
            return array_filter(explode(' ', $element->getAttribute('class')));
        }

        return [];
    }

    /**
     * Get computed style for element (tag + classes + inline)
     */
    protected function getComputedStyle(DOMElement $element): array
    {
        $tag = strtolower($element->nodeName);
        $classes = $this->getClassNames($element);
        $inlineStyle = $element->hasAttribute('style') ? $element->getAttribute('style') : null;

        // Start with tag styles
        $computedStyle = $this->cssParser->getStyleForElement($tag);

        // Apply class styles
        foreach ($classes as $class) {
            $classSelector = '.'.$class;
            $classStyles = $this->cssParser->getStyleForElement($classSelector);
            $computedStyle = array_merge($computedStyle, $classStyles);
        }

        // Apply inline styles (highest priority)
        if ($inlineStyle) {
            $inlineStyles = $this->cssParser->parseInlineStyle($inlineStyle);
            $computedStyle = array_merge($computedStyle, $inlineStyles);
        }

        return $computedStyle;
    }
}
