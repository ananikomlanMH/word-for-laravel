<?php

namespace WordForLaravel\Parsers;

use DOMDocument;
use DOMElement;
use DOMXPath;

class HtmlParser
{
    protected array $sections = [];

    protected array $currentSection = [];

    public function __construct(
        protected CssParser $cssParser
    ) {}

    /**
     * Parse HTML content
     */
    public function parse(string $html): void
    {
        $this->sections = [];
        $this->currentSection = [
            'orientation' => null,
            'header' => null,
            'footer' => null,
            'content' => [],
        ];

        // Parse CSS styles
        $this->extractAndParseStyles($html);

        // Parse HTML structure
        $dom = new DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);
        $body = $xpath->query('//body')->item(0);

        if ($body) {
            $this->parseBody($body, $xpath);
        }

        // Add the last section
        if (! empty($this->currentSection['content']) || $this->currentSection['header'] || $this->currentSection['footer']) {
            $this->sections[] = $this->currentSection;
        }
    }

    /**
     * Extract and parse CSS styles
     */
    protected function extractAndParseStyles(string $html): void
    {
        $dom = new DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);
        $styleNodes = $xpath->query('//style');

        foreach ($styleNodes as $styleNode) {
            $css = $styleNode->textContent;
            $this->cssParser->parse($css);
        }
    }

    /**
     * Parse body content
     */
    protected function parseBody(DOMElement|\DOMNode $body, DOMXPath $xpath): void
    {
        foreach ($body->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $tagName = strtolower($child->nodeName);
                // Handle special tags
                if ($tagName === 'pagebreak') {
                    $this->handlePageBreak($child);
                } elseif ($tagName === 'wordheader') {
                    $this->currentSection['header'] = $this->extractInnerHTML($child);
                } elseif ($tagName === 'wordfooter') {
                    $this->currentSection['footer'] = $this->extractInnerHTML($child);
                } else {
                    // Add regular content
                    $this->currentSection['content'][] = $child;
                }
            }
        }
    }

    /**
     * Handle page break
     */
    protected function handlePageBreak(DOMElement $element): void
    {
        // Save current section
        if (! empty($this->currentSection['content']) || $this->currentSection['header'] || $this->currentSection['footer']) {
            $this->sections[] = $this->currentSection;
        }

        // Get orientation attribute
        $orientation = $element->getAttribute('orientation');
        $orientation = $this->normalizeOrientation($orientation);

        // Start new section
        $this->currentSection = [
            'orientation' => $orientation,
            'header' => null,
            'footer' => null,
            'content' => [],
        ];
    }

    /**
     * Normalize orientation value
     */
    protected function normalizeOrientation(?string $orientation): ?string
    {
        if (in_array($orientation, [null, '', '0'], true)) {
            return null;
        }

        $orientation = strtoupper(trim($orientation));

        if (in_array($orientation, ['L', 'LANDSCAPE'])) {
            return 'landscape';
        }

        if (in_array($orientation, ['P', 'PORTRAIT'])) {
            return 'portrait';
        }

        return null;
    }

    /**
     * Extract inner HTML from element
     */
    protected function extractInnerHTML(DOMElement $element): string
    {
        $innerHTML = '';
        foreach ($element->childNodes as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return $innerHTML;
    }

    /**
     * Get parsed sections
     */
    public function getSections(): array
    {
        return $this->sections;
    }
}
