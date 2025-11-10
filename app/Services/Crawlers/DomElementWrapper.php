<?php

namespace App\Services\Crawlers;

use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Wrapper class to make DOMElement compatible with simple_html_dom element interface.
 */
class DomElementWrapper
{
    private DOMElement $element;
    private DOMXPath $xpath;

    public function __construct(DOMElement $element, DOMXPath $xpath)
    {
        $this->element = $element;
        $this->xpath = $xpath;
    }

    /**
     * Get the tag name.
     */
    public function __get(string $name)
    {
        if ($name === 'tag') {
            return $this->element->nodeName;
        }

        if ($name === 'plaintext') {
            return $this->getPlainText();
        }

        return null;
    }

    /**
     * Get plain text content (all text nodes recursively).
     */
    private function getPlainText(): string
    {
        // Use textContent property which gets all text nodes recursively
        $text = $this->element->textContent ?? '';

        // Fallback: manually traverse if textContent is empty
        if (empty(trim($text))) {
            $text = '';
            foreach ($this->element->childNodes as $node) {
                if ($node->nodeType === XML_TEXT_NODE) {
                    $text .= $node->textContent;
                } elseif ($node instanceof \DOMElement) {
                    $text .= (new DomElementWrapper($node, $this->xpath))->getPlainText();
                }
            }
        }

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Find elements within this element using CSS selector.
     */
    public function find(string $selector, $index = null): array|object|null
    {
        // Convert CSS selector to XPath
        $xpath = $this->cssToXPath($selector);

        try {
            // Query relative to this element
            $nodes = $this->xpath->query($xpath, $this->element);

            if ($nodes === false || $nodes->length === 0) {
                return $index === null ? [] : null;
            }

            $results = [];
            foreach ($nodes as $node) {
                if ($node instanceof DOMElement) {
                    $results[] = new DomElementWrapper($node, $this->xpath);
                }
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
     * Convert CSS selector to XPath (relative to current element).
     */
    private function cssToXPath(string $selector): string
    {
        // Handle multiple selectors
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
            return ".//{$tag}[contains(@{$attr}, '{$value}')]";
        }

        // Handle attribute selectors [class='value'] or [data-testid="value"]
        if (preg_match('/^\[([^\]]+)\]$/', $selector, $matches)) {
            $attr = $matches[1];
            // Handle class='value' or class="value"
            if (preg_match("/^class\s*=\s*['\"]([^'\"]+)['\"]$/", $attr, $classMatch)) {
                return ".//*[contains(concat(' ', normalize-space(@class), ' '), ' {$classMatch[1]} ')]";
            }
            // Handle data-testid='value' or data-testid="value"
            if (preg_match("/^(\w+(?:-\w+)*)\s*=\s*['\"]([^'\"]+)['\"]$/", $attr, $attrMatch)) {
                return ".//*[@{$attrMatch[1]}='{$attrMatch[2]}']";
            }
            // Handle just [attr] without value
            return ".//*[@{$attr}]";
        }

        // Handle .class selector
        if (str_starts_with($selector, '.')) {
            $class = substr($selector, 1);
            return ".//*[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]";
        }

        // Handle #id selector
        if (str_starts_with($selector, '#')) {
            $id = substr($selector, 1);
            return ".//*[@id='{$id}']";
        }

        // Handle tag.class or tag#id
        if (preg_match('/^(\w+)(\.|#)(.+)$/', $selector, $matches)) {
            $tag = $matches[1];
            $type = $matches[2];
            $value = $matches[3];

            if ($type === '.') {
                return ".//{$tag}[contains(concat(' ', normalize-space(@class), ' '), ' {$value} ')]";
            } else {
                return ".//{$tag}[@id='{$value}']";
            }
        }

        // Simple tag selector
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $selector)) {
            return ".//{$selector}";
        }

        // Default
        return ".//{$selector}";
    }

    /**
     * Get attribute value.
     */
    public function getAttribute(string $name): ?string
    {
        return $this->element->getAttribute($name) ?: null;
    }

    /**
     * Get parent element.
     */
    public function parent(): ?object
    {
        $parent = $this->element->parentNode;
        if ($parent instanceof DOMElement) {
            return new DomElementWrapper($parent, $this->xpath);
        }
        return null;
    }

    /**
     * Get the underlying DOMElement.
     */
    public function getDomElement(): DOMElement
    {
        return $this->element;
    }
}

