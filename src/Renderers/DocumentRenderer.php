<?php

namespace WordForLaravel\Renderers;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use WordForLaravel\Parsers\CssParser;
use WordForLaravel\Renderers\Elements\HeadingRenderer;
use WordForLaravel\Renderers\Elements\ImageRenderer;
use WordForLaravel\Renderers\Elements\ListRenderer;
use WordForLaravel\Renderers\Elements\ParagraphRenderer;
use WordForLaravel\Renderers\Elements\TableRenderer;

class DocumentRenderer
{
    protected HeadingRenderer $headingRenderer;

    protected ParagraphRenderer $paragraphRenderer;

    protected TableRenderer $tableRenderer;

    protected ListRenderer $listRenderer;

    protected ImageRenderer $imageRenderer;

    public function __construct(
        protected PhpWord $phpWord,
        protected CssParser $cssParser
    ) {
        $this->headingRenderer = new HeadingRenderer($cssParser);
        $this->paragraphRenderer = new ParagraphRenderer($cssParser);
        $this->tableRenderer = new TableRenderer($cssParser);
        $this->listRenderer = new ListRenderer($cssParser);
        $this->imageRenderer = new ImageRenderer($cssParser);
    }

    /**
     * Render document sections
     */
    public function render(array $sections, string $defaultOrientation = 'portrait'): void
    {
        foreach ($sections as $sectionData) {
            $orientation = $sectionData['orientation'] ?? $defaultOrientation;
            $section = $this->createSection($orientation);

            // Add header if present
            if (! empty($sectionData['header'])) {
                $this->addHeaderFooter($section, $sectionData['header'], true);
            }

            // Add footer if present
            if (! empty($sectionData['footer'])) {
                $this->addHeaderFooter($section, $sectionData['footer'], false);
            }

            // Render content
            foreach ($sectionData['content'] as $element) {
                $this->renderElement($element, $section);
            }
        }
    }

    /**
     * Create section with orientation
     */
    protected function createSection(string $orientation): Section
    {
        $sectionStyle = [
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1000,
            'marginRight' => 1000,
        ];

        if ($orientation === 'landscape') {
            $sectionStyle['orientation'] = 'landscape';
        }

        return $this->phpWord->addSection($sectionStyle);
    }

    /**
     * Add header or footer
     */
    protected function addHeaderFooter(Section $section, string $html, bool $isHeader): void
    {
        $container = $isHeader ? $section->addHeader() : $section->addFooter();

        $html = <<<HTML
            <body>
            {$html}
            </body>
        HTML;

        $dom = new \DOMDocument;
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);
        $body = $xpath->query('//body')->item(0);

        if ($body) {
            foreach ($body->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $this->renderElement($child, $container);
                }
            }
        }
    }

    /**
     * Render single element
     */
    protected function renderElement(\DOMElement $element, $container): void
    {
        $tagName = strtolower($element->nodeName);

        switch ($tagName) {
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $this->headingRenderer->render($element, $container, $tagName);
                break;
            case 'p':
                $this->paragraphRenderer->render($element, $container);
                break;
            case 'img':
                $this->imageRenderer->render($element, $container);
                break;
            case 'table':
                $this->tableRenderer->render($element, $container);
                break;
            case 'ul':
            case 'ol':
                $this->listRenderer->render($element, $container, $tagName);
                break;
            case 'hr':
            case 'br':
                $container->addTextBreak();
                break;
            default:
                // Process children for div, span, etc.
                if ($element->hasChildNodes()) {
                    foreach ($element->childNodes as $child) {
                        if ($child->nodeType === XML_ELEMENT_NODE) {
                            $this->renderElement($child, $container);
                        } elseif ($child->nodeType === XML_TEXT_NODE) {
                            $text = trim($child->textContent);
                            if (! empty($text)) {
                                $container->addText($text);
                            }
                        }
                    }
                }
                break;
        }
    }
}
