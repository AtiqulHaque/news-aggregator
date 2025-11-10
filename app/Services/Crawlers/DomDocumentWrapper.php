<?php

namespace App\Services\Crawlers;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Wrapper class to make DOMDocument compatible with simple_html_dom interface.
 * This allows us to use DOMDocument for large HTML files while maintaining
 * the same API as simple_html_dom.
 */
class DomDocumentWrapper
{
    private DOMDocument $dom;
    private DOMXPath $xpath;

    public function __construct(DOMDocument $dom)
    {
        $this->dom = $dom;
        $this->xpath = new DOMXPath($dom);
    }

    /**
     * Find elements using CSS selector (converted to XPath).
     * Supports basic selectors: tag, .class, #id, [attribute], [attribute=value]
     */
    public function find(string $selector, $index = null): array|object|null
    {
        // Convert CSS selector to XPath
        $xpath = $this->cssToXPath($selector);

        try {
            $nodes = $this->xpath->query($xpath);

            if ($nodes === false || $nodes->length === 0) {
                return $index === null ? [] : null;
            }

            $results = [];
            foreach ($nodes as $node) {
                $results[] = new DomElementWrapper($node, $this->xpath);
            }

            if ($index !== null) {
                return isset($results[$index]) ? $results[$index] : null;
            }

            return $results;
        } catch (\Exception $e) {
            return $index === null ? [] : null;
        }
    }

    /**
     * Convert CSS selector to XPath expression.
     * Supports: tag, .class, #id, [attr], [attr=value], [attr*="value"], tag.class, tag#id
     */
    private function cssToXPath(string $selector): string
    {
        // Handle multiple selectors (comma-separated)
        if (str_contains($selector, ',')) {
            $selectors = array_map('trim', explode(',', $selector));
            $xpaths = array_map([$this, 'cssToXPath'], $selectors);
            return implode(' | ', $xpaths);
        }

        $selector = trim($selector);

        // Handle attribute contains selector: tag[attr*="value"] or [attr*="value"]
        if (preg_match('/^(\w+)?\[([^\]]*)\*=["\']([^"\']+)["\']\]$/', $selector, $matches)) {
            $tag = $matches[1] ?: '*';
            $attr = trim($matches[2]);
            $value = $matches[3];
            return "//{$tag}[contains(@{$attr}, '{$value}')]";
        }

        // Handle attribute selectors [class='value'] or [class="value"] or [data-testid="value"]
        if (preg_match('/^\[([^\]]+)\]$/', $selector, $matches)) {
            $attr = $matches[1];
            // Handle class='value' or class="value"
            if (preg_match("/^class\s*=\s*['\"]([^'\"]+)['\"]$/", $attr, $classMatch)) {
                return "//*[contains(concat(' ', normalize-space(@class), ' '), ' {$classMatch[1]} ')]";
            }
            // Handle data-testid='value' or data-testid="value"
            if (preg_match("/^(\w+(?:-\w+)*)\s*=\s*['\"]([^'\"]+)['\"]$/", $attr, $attrMatch)) {
                return "//*[@{$attrMatch[1]}='{$attrMatch[2]}']";
            }
            // Handle just [attr] without value
            return "//*[@{$attr}]";
        }

        // Handle .class selector
        if (str_starts_with($selector, '.')) {
            $class = substr($selector, 1);
            return "//*[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]";
        }

        // Handle #id selector
        if (str_starts_with($selector, '#')) {
            $id = substr($selector, 1);
            return "//*[@id='{$id}']";
        }

        // Handle tag.class or tag#id
        if (preg_match('/^(\w+)(\.|#)(.+)$/', $selector, $matches)) {
            $tag = $matches[1];
            $type = $matches[2];
            $value = $matches[3];

            if ($type === '.') {
                return "//{$tag}[contains(concat(' ', normalize-space(@class), ' '), ' {$value} ')]";
            } else {
                return "//{$tag}[@id='{$value}']";
            }
        }

        // Simple tag selector
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $selector)) {
            return "//{$selector}";
        }

        // Default: try to find by tag name
        return "//{$selector}";
    }

    /**
     * Get the underlying DOMDocument.
     */
    public function getDomDocument(): DOMDocument
    {
        return $this->dom;
    }

    /**
     * Clean up resources.
     */
    public function clear(): void
    {
        // DOMDocument doesn't need explicit cleanup in PHP
    }
}

