<?php

namespace WordForLaravel\Renderers\Elements;

use DOMElement;

/**
 * Image Renderer
 */
class ImageRenderer extends BaseElementRenderer
{
    /**
     * Render image element
     */
    public function render(DOMElement $node, $container): void
    {
        // Get image source
        $src = $node->getAttribute('src');

        if (empty($src)) {
            return;
        }

        // Get inline style
        $inlineStyle = $node->hasAttribute('style') ? $node->getAttribute('style') : null;
        $cssStyle = $this->cssParser->getStyleForElement('img', $inlineStyle);

        // Convert to PhpWord image style
        $imageStyle = $this->convertToImageStyle($node, $cssStyle);

        try {
            // Determine if it's a data URI, URL, or file path
            $imageSource = $this->resolveImageSource($src);

            // Add image to container
            $container->addImage($imageSource, $imageStyle);
        } catch (\Exception $exception) {
            // Fallback: add text indicating missing image
            $container->addText('[Image: '.basename($src).']', [
                'color' => '999999',
                'italic' => true,
                'size' => 10,
            ]);
        }
    }

    /**
     * Convert CSS styles to PhpWord image style
     */
    protected function convertToImageStyle(DOMElement $node, array $cssStyles): array
    {
        $imageStyle = [];

        // Width from CSS or HTML attribute
        if (isset($cssStyles['width'])) {
            $imageStyle['width'] = $this->convertDimensionToPoints($cssStyles['width']);
        } elseif ($node->hasAttribute('width')) {
            $imageStyle['width'] = $this->convertDimensionToPoints($node->getAttribute('width'));
        }

        // Height from CSS or HTML attribute
        if (isset($cssStyles['height'])) {
            $imageStyle['height'] = $this->convertDimensionToPoints($cssStyles['height']);
        } elseif ($node->hasAttribute('height')) {
            $imageStyle['height'] = $this->convertDimensionToPoints($node->getAttribute('height'));
        }

        // Alignment
        if (isset($cssStyles['text-align'])) {
            $alignmentMap = [
                'left' => 'left',
                'center' => 'center',
                'right' => 'right',
            ];
            $imageStyle['alignment'] = $alignmentMap[$cssStyles['text-align']] ?? 'left';
        } elseif (isset($cssStyles['float'])) {
            $floatMap = [
                'left' => 'left',
                'right' => 'right',
            ];
            $imageStyle['alignment'] = $floatMap[$cssStyles['float']] ?? 'left';
            $imageStyle['wrappingStyle'] = 'square';
        }

        // Display block centers the image
        if (isset($cssStyles['display']) && $cssStyles['display'] === 'block' && (isset($cssStyles['margin-left']) && $cssStyles['margin-left'] === 'auto' && isset($cssStyles['margin-right']) && $cssStyles['margin-right'] === 'auto')) {
            $imageStyle['alignment'] = 'center';
        }

        // Margins
        if (isset($cssStyles['margin-top'])) {
            $imageStyle['marginTop'] = $this->convertDimensionToInches($cssStyles['margin-top']);
        }

        if (isset($cssStyles['margin-left'])) {
            $imageStyle['marginLeft'] = $this->convertDimensionToInches($cssStyles['margin-left']);
        }

        // Wrapping style from CSS
        if (isset($cssStyles['position']) && $cssStyles['position'] === 'absolute') {
            $imageStyle['wrappingStyle'] = 'behind';
        }

        // Positioning
        if (isset($cssStyles['vertical-align'])) {
            $valignMap = [
                'top' => -1,
                'middle' => 0,
                'bottom' => 1,
            ];
            if (isset($valignMap[$cssStyles['vertical-align']])) {
                $imageStyle['marginTop'] = $valignMap[$cssStyles['vertical-align']];
            }
        }

        // HTML alt attribute for accessibility (stored but not rendered)
        if ($node->hasAttribute('alt')) {
            $imageStyle['description'] = $node->getAttribute('alt');
        }

        return $imageStyle;
    }

    /**
     * Resolve image source (data URI, URL, or file path)
     */
    protected function resolveImageSource(string $src): string
    {
        // Data URI
        if (strpos($src, 'data:image') === 0) {
            return $this->dataUriToTempFile($src);
        }

        // HTTP/HTTPS URL
        if (strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0) {
            return $this->downloadImageToTemp($src);
        }

        // Relative path - resolve from Laravel public path
        if (! file_exists($src)) {
            $publicPath = public_path($src);
            if (file_exists($publicPath)) {
                return $publicPath;
            }

            // Try storage path
            $storagePath = storage_path('app/public/'.ltrim($src, '/'));
            if (file_exists($storagePath)) {
                return $storagePath;
            }

            // Try resource path
            $resourcePath = resource_path($src);
            if (file_exists($resourcePath)) {
                return $resourcePath;
            }
        }

        return $src;
    }

    /**
     * Convert data URI to temporary file
     */
    protected function dataUriToTempFile(string $dataUri): string
    {
        // Extract data from data URI
        preg_match('/^data:image\/(\w+);base64,(.+)$/', $dataUri, $matches);

        if (count($matches) !== 3) {
            throw new \InvalidArgumentException('Invalid data URI format');
        }

        $extension = $matches[1];
        $data = base64_decode($matches[2]);

        // Create temporary file
        $tempFile = sys_get_temp_dir().'/'.uniqid('word_image_', true).'.'.$extension;
        file_put_contents($tempFile, $data);

        return $tempFile;
    }

    /**
     * Download remote image to temporary file
     */
    protected function downloadImageToTemp(string $url): string
    {
        // Get file extension from URL
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = 'jpg';
        }

        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
            ],
        ]);

        // Download image
        $imageData = @file_get_contents($url, false, $context);

        if ($imageData === false) {
            throw new \RuntimeException('Failed to download image from: ' . $url);
        }

        // Create temporary file
        $tempFile = sys_get_temp_dir().'/'.uniqid('word_image_', true).'.'.$extension;
        file_put_contents($tempFile, $imageData);

        return $tempFile;
    }

    /**
     * Convert CSS dimension to points
     */
    protected function convertDimensionToPoints(string $value): float
    {
        $value = strtolower(trim($value));

        // Already in points
        if (strpos($value, 'pt') !== false) {
            return (float) str_replace('pt', '', $value);
        }

        // Pixels to points (1px = 0.75pt)
        if (strpos($value, 'px') !== false) {
            return (float) str_replace('px', '', $value) * 0.75;
        }

        // Inches to points (1in = 72pt)
        if (strpos($value, 'in') !== false) {
            return (float) str_replace('in', '', $value) * 72;
        }

        // Centimeters to points (1cm = 28.35pt)
        if (strpos($value, 'cm') !== false) {
            return (float) str_replace('cm', '', $value) * 28.35;
        }

        // Millimeters to points (1mm = 2.835pt)
        if (strpos($value, 'mm') !== false) {
            return (float) str_replace('mm', '', $value) * 2.835;
        }

        // Percentage - cannot convert without context, return as-is
        if (strpos($value, '%') !== false) {
            return (float) str_replace('%', '', $value);
        }

        // No unit, assume pixels
        return (float) $value * 0.75;
    }

    /**
     * Convert CSS dimension to inches (for margins)
     */
    protected function convertDimensionToInches(string $value): float
    {
        $value = strtolower(trim($value));

        // Already in inches
        if (strpos($value, 'in') !== false) {
            return (float) str_replace('in', '', $value);
        }

        // Points to inches (72pt = 1in)
        if (strpos($value, 'pt') !== false) {
            return (float) str_replace('pt', '', $value) / 72;
        }

        // Pixels to inches (96px = 1in)
        if (strpos($value, 'px') !== false) {
            return (float) str_replace('px', '', $value) / 96;
        }

        // Centimeters to inches (2.54cm = 1in)
        if (strpos($value, 'cm') !== false) {
            return (float) str_replace('cm', '', $value) / 2.54;
        }

        // Millimeters to inches (25.4mm = 1in)
        if (strpos($value, 'mm') !== false) {
            return (float) str_replace('mm', '', $value) / 25.4;
        }

        // No unit, assume pixels
        return (float) $value / 96;
    }

    /**
     * Get image dimensions from file
     */
    protected function getImageDimensions(string $path): ?array
    {
        try {
            $size = @getimagesize($path);
            if ($size !== false) {
                return [
                    'width' => $size[0],
                    'height' => $size[1],
                ];
            }
        } catch (\Exception $exception) {
            // Ignore errors
        }

        return null;
    }
}
