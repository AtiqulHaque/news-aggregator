<?php

namespace App\Services\Crawlers;

use App\Models\NewsSource;
use App\Services\Crawlers\Contracts\NewsCrawlerInterface;
use App\Services\Crawlers\DomDocumentWrapper;
use App\Services\Crawlers\DomElementWrapper;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractCrawlerService implements NewsCrawlerInterface
{
    /**
     * Default priority for crawlers.
     */
    protected int $priority = 0;

    /**
     * Get the priority of this crawler.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Fetch HTML content from a URL.
     */
    protected function fetchHtml(string $url, array $headers = []): string
    {
        $defaultHeaders = [
            'User-Agent' => 'Mozilla/5.0 (compatible; NewsBot/1.0)',
        ];

        $response = Http::timeout(30)
            ->withHeaders(array_merge($defaultHeaders, $headers))
            ->get($url);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch URL: {$url} - Status: {$response->status()}");
        }

        return $response->body();
    }

    /**
     * Parse HTML string using DOMDocument for large files, simple_html_dom for smaller files.
     *
     * @throws \Exception if HTML cannot be parsed
     */
    protected function parseHtml(string $html): ?object
    {
        // Check if HTML is empty or too short
        if (empty($html) || strlen(trim($html)) < 100) {
            Log::error('HTML content too short', ['length' => strlen($html)]);
            throw new \Exception("HTML content is empty or too short. Length: " . strlen($html));
        }

        // Normalize line endings and clean up
        $html = str_replace(["\r\n", "\r"], "\n", $html);

        // Remove null bytes and other problematic characters first
        $html = str_replace(["\0", "\x00"], '', $html);

        $htmlLength = strlen($html);
        Log::info('Preparing to parse HTML', ['html_length' => $htmlLength]);

        // For large files (>600KB), use DOMDocument which can handle much larger files
        // For smaller files, use simple_html_dom for better CSS selector support
        if ($htmlLength > 600000) {
            return $this->parseHtmlWithDomDocument($html);
        } else {
            return $this->parseHtmlWithSimpleDom($html);
        }
    }

    /**
     * Parse HTML using PHP's native DOMDocument (handles large files).
     */
    protected function parseHtmlWithDomDocument(string $html): object
    {
        Log::info('Using DOMDocument parser for large HTML file', ['html_length' => strlen($html)]);

        // Try to extract body content first to reduce memory usage
        $bodyContent = null;
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $bodyMatches)) {
            $bodyContent = $bodyMatches[1];
            Log::info('Extracted body content for DOMDocument parsing', ['body_length' => strlen($bodyContent)]);
        }

        // Create a wrapper class to make DOMDocument compatible with simple_html_dom interface
        $dom = new \DOMDocument();

        // Suppress errors for malformed HTML
        libxml_use_internal_errors(true);

        // Try parsing with body content first (smaller, faster)
        $htmlToParse = $bodyContent ? '<html><body>' . $bodyContent . '</body></html>' : $html;

        // Load HTML - DOMDocument can handle large files
        $loaded = @$dom->loadHTML('<?xml encoding="UTF-8">' . $htmlToParse, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Clear libxml errors
        libxml_clear_errors();

        if (!$loaded || !$dom->documentElement) {
            // Try without XML declaration
            $loaded = @$dom->loadHTML($htmlToParse, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
        }

        // If body extraction failed, try with full HTML
        if ((!$loaded || !$dom->documentElement) && $bodyContent) {
            Log::info('Body-only parsing failed, trying full HTML');
            $dom = new \DOMDocument();
            $loaded = @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            if (!$loaded || !$dom->documentElement) {
                $loaded = @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();
            }
        }

        if (!$loaded || !$dom->documentElement) {
            Log::error('DOMDocument failed to parse HTML', ['html_length' => strlen($html)]);
            throw new \Exception("DOMDocument failed to parse HTML content. Length: " . strlen($html));
        }

        Log::info('DOMDocument successfully parsed HTML', ['html_length' => strlen($html)]);

        // Return a wrapper that provides simple_html_dom-like interface
        return new DomDocumentWrapper($dom);
    }

    /**
     * Parse HTML using simple_html_dom (for smaller files with better CSS selector support).
     */
    protected function parseHtmlWithSimpleDom(string $html): object
    {
        Log::info('Using simple_html_dom parser', ['html_length' => strlen($html)]);

        // Ensure HTML is under MAX_FILE_SIZE limit (600KB)
        $maxParserSize = 550000; // 550KB - safely under the 600KB limit

        if (strlen($html) > $maxParserSize) {
            Log::warning('HTML exceeds simple_html_dom limit, truncating', [
                'original_length' => strlen($html),
                'max_parser_size' => $maxParserSize
            ]);
            $html = mb_substr($html, 0, $maxParserSize);
        }

        try {
            $dom = @HtmlDomParser::str_get_html($html);

            if (!$dom || $dom === false) {
                // Try with wrapped HTML structure if needed
                if (!str_contains($html, '<html')) {
                    $html = '<html><body>' . $html . '</body></html>';
                    $dom = @HtmlDomParser::str_get_html($html);
                }

                if (!$dom || $dom === false) {
                    throw new \Exception("Failed to parse HTML content with simple_html_dom. Length: " . strlen($html));
                }
            }

            return $dom;
        } catch (\Throwable $e) {
            Log::error('simple_html_dom parsing failed', ['error' => $e->getMessage()]);
            throw new \Exception("HTML parsing error: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolve relative URL to absolute URL.
     */
    protected function resolveUrl(string $url, string $baseUrl): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Extract text content from DOM element.
     */
    protected function extractText(?object $element): string
    {
        if (!$element) {
            return '';
        }

        // Handle DomElementWrapper
        if ($element instanceof DomElementWrapper) {
            return trim($element->__get('plaintext') ?? '');
        }

        // Handle simple_html_dom elements
        return trim($element->plaintext ?? '');
    }

    /**
     * Extract attribute value from DOM element.
     */
    protected function extractAttribute(?object $element, string $attribute): ?string
    {
        if (!$element) {
            return null;
        }

        // Handle DomElementWrapper
        if ($element instanceof DomElementWrapper) {
            return $element->getAttribute($attribute);
        }

        // Handle simple_html_dom elements
        return $element->getAttribute($attribute) ?? null;
    }

    /**
     * Log crawler activity.
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $context['crawler'] = $this->getName();

        Log::{$level}($message, $context);
    }

    /**
     * Clean up DOM object.
     */
    protected function cleanupDom(?object $dom): void
    {
        if ($dom && method_exists($dom, 'clear')) {
            $dom->clear();
        }
        unset($dom);
    }

    /**
     * Create article array structure.
     * @param string $title
     * @param string $url
     * @param string $content
     * @param string|null $author
     * @param string|null $publishedAt
     * @param array $metadata
     * @return array
     */
    protected function createArticle(
        string $title,
        string $url,
        string $content = '',
        ?string $author = null,
        ?string $publishedAt = null,
        array $metadata = []
    ): array {
        return [
            'title' => $title,
            'content' => $content,
            'url' => $url,
            'author' => $author,
            'published_at' => $publishedAt,
            'summary' => mb_substr($content, 0, 200),
            'metadata' => array_merge([
                'source_type' => 'website',
                'crawler' => $this->getName(),
            ], $metadata),
        ];
    }
}

