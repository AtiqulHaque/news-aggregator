<?php

namespace App\Services\Crawlers;

use App\Models\NewsSource;
use Illuminate\Support\Facades\Log;

class CnnNewsCrawlerService extends AbstractCrawlerService
{
    protected int $priority = 100; // High priority for CNN-specific crawler

    public function getName(): string
    {
        return 'CNN News Crawler';
    }

    public function supports(NewsSource $source): bool
    {
        $url = strtolower($source->base_url);
        return str_contains($url, 'edition.cnn.com');
    }

    public function crawl(NewsSource $source): array
    {
        $articles = [];

        try {
            Log::info('CNN News crawl started', ['source_id' => $source->id, 'base_url' => $source->base_url]);

            // Ensure URL has protocol
            $url = $source->base_url;
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . ltrim($url, '/');
                Log::info('CNN News URL fixed', ['original' => $source->base_url, 'fixed' => $url]);
            }

            // Try RSS feed first
            $html = $this->fetchHtml($url);



            // Wrap parseWebsite in try-catch to ensure we always get articles or handle gracefully
            try {
                $articles = $this->parseWebsite($html, $source);
            } catch (\Throwable $parseError) {
                // If parseWebsite itself throws (shouldn't happen, but be safe)
                Log::error('CNN News parseWebsite threw exception', [
                    'source_id' => $source->id,
                    'error' => $parseError->getMessage(),
                    'error_class' => get_class($parseError)
                ]);

                // Try to create a basic fallback article
                try {
                    $content = mb_substr(@strip_tags($html) ?: $html, 0, 2000) ?: 'Content unavailable';
                    $articles[] = $this->createArticle(
                        'CNN News - ' . $source->name,
                        $source->base_url,
                        $content,
                        null,
                        null,
                        ['source' => 'cnn', 'fallback' => true, 'parse_error' => $parseError->getMessage()]
                    );
                } catch (\Throwable $fallbackError) {
                    Log::error('CNN News even fallback creation failed', [
                        'source_id' => $source->id,
                        'parse_error' => $parseError->getMessage(),
                        'fallback_error' => $fallbackError->getMessage()
                    ]);
                    $articles = [];
                }
            }

            $this->log('info', 'CNN News crawl completed', ['source_id' => $source->id, 'articles_count' => count($articles)]);
            Log::info('CNN News crawl completed', ['source_id' => $source->id, 'articles_count' => count($articles)]);
        } catch (\Throwable $e) {
            $this->log('error', 'CNN News crawl failed', [
                'source_id' => $source->id,
                'base_url' => $source->base_url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Log::error('CNN News crawl failed', [
                'source_id' => $source->id,
                'base_url' => $source->base_url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Only throw if we don't have any articles (fallback should have created at least one)
            if (empty($articles)) {
                throw $e;
            } else {
                // We have fallback articles, log warning but don't throw
                Log::warning('CNN News crawl had errors but returned fallback articles', [
                    'source_id' => $source->id,
                    'articles_count' => count($articles),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $articles;
    }



    /**
     * Parse CNN website HTML.
     * Step 1: Find all anchor tags with class container__link and extract hrefs
     * Step 2: For each link, fetch the article page and parse headline__text and article__content
     */
    public function parseWebsite(string $html, NewsSource $source): array
    {
        $articles = [];
        $dom = null;

        try {
            $dom = $this->parseHtml($html);
        } catch (\Throwable $e) {
            Log::error('CNN News parseHtml failed', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);

            // If HTML parsing fails, create a fallback article
            Log::info('CNN News creating fallback article due to parse failure', [
                'source_id' => $source->id,
                'error' => $e->getMessage()
            ]);

            try {
                $content = '';
                if (!empty($html)) {
                    $stripped = @strip_tags($html);
                    $content = mb_substr($stripped ?: $html, 0, 2000);
                }
                if (empty($content)) {
                    $content = 'Content unavailable - HTML parsing failed';
                }

                $articles[] = $this->createArticle(
                    'CNN News - ' . $source->name,
                    $source->base_url,
                    $content,
                    null,
                    null,
                    ['source' => 'cnn', 'fallback' => true, 'parse_error' => $e->getMessage()]
                );

                return $articles;
            } catch (\Throwable $fallbackError) {
                Log::error('CNN News fallback article creation also failed', [
                    'source_id' => $source->id,
                    'original_error' => $e->getMessage(),
                    'fallback_error' => $fallbackError->getMessage()
                ]);
                return [];
            }
        }

        try {
            // Step 1: Find all anchor tags with class container__link
            Log::info('CNN News: Finding container__link anchors', ['base_url' => $source->base_url]);

            // Try multiple selector variations for container__link
            $linkSelectors = [
                'a.container__link',
                'a[class*="container__link"]',
                'a[class="container__link"]',
            ];

            $articleLinks = [];
            foreach ($linkSelectors as $selector) {
                $linkElements = $dom->find($selector);
                if (!empty($linkElements)) {
                    Log::info('CNN News: Found links with selector', [
                        'selector' => $selector,
                        'count' => is_array($linkElements) ? count($linkElements) : 1
                    ]);

                    if (!is_array($linkElements)) {
                        $linkElements = [$linkElements];
                    }

                    foreach ($linkElements as $linkElement) {
                        $href = $this->extractAttribute($linkElement, 'href');
                        if ($href) {
                            // Ensure base URL has protocol
                            $baseUrl = $source->base_url;
                            if (!str_starts_with($baseUrl, 'http://') && !str_starts_with($baseUrl, 'https://')) {
                                $baseUrl = 'https://' . ltrim($baseUrl, '/');
                            }

                            $fullUrl = $this->resolveUrl($href, $baseUrl);

                            // Ensure the resolved URL is valid
                            if (filter_var($fullUrl, FILTER_VALIDATE_URL)) {
                                if (!in_array($fullUrl, $articleLinks)) {
                                    $articleLinks[] = $fullUrl;
                                }
                            } else {
                                Log::warning('CNN News: Invalid URL resolved', [
                                    'href' => $href,
                                    'base_url' => $baseUrl,
                                    'resolved' => $fullUrl
                                ]);
                            }
                        }
                    }

                    if (!empty($articleLinks)) {
                        break; // Found links, no need to try other selectors
                    }
                }
            }

            Log::info('CNN News: Extracted article links', [
                'count' => count($articleLinks),
                'links' => array_slice($articleLinks, 0, 5) // Log first 5 for debugging
            ]);

            if (empty($articleLinks)) {
                Log::warning('CNN News: No article links found, creating fallback article');
                $titleElement = $dom->find('title', 0);
                $pageTitle = $this->extractText($titleElement) ?: 'CNN News';

                $articles[] = $this->createArticle(
                    $pageTitle,
                    $source->base_url,
                    mb_substr(strip_tags($html), 0, 2000),
                    null,
                    null,
                    ['source' => 'cnn', 'fallback' => true, 'reason' => 'no_links_found']
                );
                return $articles;
            }

            // Step 2: Fetch and parse each article page
            $maxArticles = 50; // Limit to prevent too many requests
            $processedCount = 0;

            foreach (array_slice($articleLinks, 0, $maxArticles) as $articleUrl) {
                try {
                    Log::info('CNN News: Fetching article', ['url' => $articleUrl, 'index' => $processedCount + 1]);

                    // Fetch the article page
                    $articleHtml = $this->fetchHtml($articleUrl);

                    // Parse the article HTML
                    $articleDom = $this->parseHtml($articleHtml);

                    // Find headline__text class
                    $headlineSelectors = [
                        '.headline__text',
                        '[class*="headline__text"]',
                        '[class="headline__text"]',
                        'h1.headline__text',
                    ];

                    $title = '';
                    foreach ($headlineSelectors as $selector) {
                        $headlineElement = $articleDom->find($selector, 0);
                        if ($headlineElement) {
                            $title = $this->extractText($headlineElement);
                            if (!empty($title)) {
                                Log::info('CNN News: Found headline', ['title' => $title, 'selector' => $selector]);
                                break;
                            }
                        }
                    }

                    // If no headline found, try fallback selectors
                    if (empty($title)) {
                        $fallbackSelectors = ['h1', 'title', '.article__headline', '[data-module="ArticleHeadline"]'];
                        foreach ($fallbackSelectors as $selector) {
                            $headlineElement = $articleDom->find($selector, 0);
                            if ($headlineElement) {
                                $title = $this->extractText($headlineElement);
                                if (!empty($title)) {
                                    break;
                                }
                            }
                        }
                    }

                    // Extract author from byline__authors class
                    $author = '';
                    $authorSelectors = [
                        '.byline__authors',
                        '[class*="byline__authors"]',
                        '[class="byline__authors"]',
                        '.byline__author',
                        '[class*="byline__author"]',
                        '.author',
                        '[class*="author"]',
                        '[data-module="ArticleAuthor"]',
                    ];

                    foreach ($authorSelectors as $selector) {
                        $authorElement = $articleDom->find($selector, 0);
                        if ($authorElement) {
                            $author = $this->extractText($authorElement);
                            $author = trim($author);
                            if (!empty($author)) {
                                Log::info('CNN News: Found author', ['author' => $author, 'selector' => $selector]);
                                break;
                            }
                        }
                    }

                    // If no author found, try fallback selectors
                    if (empty($author)) {
                        $fallbackAuthorSelectors = [
                            '.byline',
                            '[class*="byline"]',
                            '[rel="author"]',
                            '.article__author',
                            '[data-module="Byline"]',
                        ];
                        foreach ($fallbackAuthorSelectors as $selector) {
                            $authorElement = $articleDom->find($selector, 0);
                            if ($authorElement) {
                                $author = $this->extractText($authorElement);
                                $author = trim($author);
                                if (!empty($author)) {
                                    Log::info('CNN News: Found author with fallback selector', [
                                        'author' => $author,
                                        'selector' => $selector
                                    ]);
                                    break;
                                }
                            }
                        }
                    }

                    // Find article__content class - extract all paragraphs
                    $contentSelectors = [
                        '.article__content',
                        '[class*="article__content"]',
                        '[class="article__content"]',
                        '.article-body',
                        '[class*="article-body"]',
                        '.l-container',
                        '[data-module="ArticleBody"]',
                    ];

                    $content = '';
                    $contentElement = null;

                    foreach ($contentSelectors as $selector) {
                        Log::info('CNN News: Trying to find content element with selector', ['selector' => $selector]);
                        $contentElement = $articleDom->find($selector, 0);
                        if ($contentElement) {
                            Log::info('CNN News: Found content element', [
                                'selector' => $selector,
                                'element_type' => get_class($contentElement)
                            ]);

                            // Primary strategy: Extract all paragraphs and concatenate with newlines
                            $paragraphs = $contentElement->find('p');
                            if (!empty($paragraphs)) {
                                $paragraphTexts = [];

                                // Handle both array and single element
                                if (!is_array($paragraphs)) {
                                    $paragraphs = [$paragraphs];
                                }

                                Log::info('CNN News: Found paragraphs in content element', [
                                    'paragraph_count' => count($paragraphs)
                                ]);

                                foreach ($paragraphs as $para) {
                                    $paraText = $this->extractText($para);
                                    $paraText = trim($paraText);

                                    // Only add non-empty paragraphs with meaningful content
                                    if (!empty($paraText) && strlen($paraText) > 10) {
                                        $paragraphTexts[] = $paraText;
                                    }
                                }

                                if (!empty($paragraphTexts)) {
                                    // Concatenate all paragraphs with newline
                                    $content = implode("\n\n", $paragraphTexts);
                                    Log::info('CNN News: Extracted content from paragraphs', [
                                        'paragraph_count' => count($paragraphTexts),
                                        'content_length' => strlen($content),
                                        'preview' => mb_substr($content, 0, 200)
                                    ]);
                                    break; // Successfully extracted, exit loop
                                }
                            }

                            // Fallback: If no paragraphs found, try extracting text directly
                            if (empty($content)) {
                                $content = $this->extractText($contentElement);
                                if (!empty(trim($content))) {
                                    Log::info('CNN News: Extracted content directly from element', [
                                        'content_length' => strlen($content),
                                        'preview' => mb_substr($content, 0, 200)
                                    ]);
                                    break;
                                }
                            }

                            // Last resort: Try to get all text elements (divs, spans with content)
                            if (empty($content)) {
                                $allTextElements = $contentElement->find('p, div, span');
                                if (!empty($allTextElements)) {
                                    if (!is_array($allTextElements)) {
                                        $allTextElements = [$allTextElements];
                                    }
                                    $textParts = [];
                                    foreach ($allTextElements as $textEl) {
                                        $text = $this->extractText($textEl);
                                        $text = trim($text);
                                        // Only add meaningful text (more than 20 chars to avoid navigation/ads)
                                        if (!empty($text) && strlen($text) > 20) {
                                            $textParts[] = $text;
                                        }
                                    }
                                    if (!empty($textParts)) {
                                        // Remove duplicates and join with newlines
                                        $content = implode("\n\n", array_unique($textParts));
                                        Log::info('CNN News: Extracted content from text elements', [
                                            'element_count' => count($textParts),
                                            'content_length' => strlen($content)
                                        ]);
                                        break;
                                    }
                                }
                            }
                        }
                    }


                    // Final cleanup of content
                    if (!empty($content)) {
                        // Remove excessive whitespace
                        $content = preg_replace('/\n{3,}/', "\n\n", $content);
                        $content = trim($content);
                    }

                    // Log content extraction result

                    // Only create article if we have at least a title or content
                    if (!empty($title) || !empty($content)) {
                        $articles[] = $this->createArticle(
                            $title,
                            $articleUrl,
                            $content,
                            !empty($author) ? $author : null,
                            null,
                            ['source' => 'cnn', 'parsed_from' => 'article_page', 'content_empty' => empty($content)]
                        );

                        $processedCount++;
                        Log::info('CNN News: Article processed successfully', [
                            'title' => $title,
                            'author' => $author ?: 'N/A',
                            'url' => $articleUrl,
                            'content_length' => strlen($content),
                            'content_preview' => mb_substr($content, 0, 100)
                        ]);
                    } else {
                        Log::warning('CNN News: Article has no title or content, skipping', ['url' => $articleUrl]);
                    }

                    // Cleanup article DOM
                    $this->cleanupDom($articleDom);

                } catch (\Throwable $articleError) {
                    Log::error('CNN News: Failed to process article', [
                        'url' => $articleUrl,
                        'error' => $articleError->getMessage(),
                        'error_class' => get_class($articleError)
                    ]);
                    // Continue to next article instead of failing completely
                    continue;
                }

                // Add small delay to avoid overwhelming the server
                if ($processedCount < count(array_slice($articleLinks, 0, $maxArticles))) {
                    usleep(500000); // 0.5 second delay
                }
            }

            Log::info('CNN News: Finished processing articles', [
                'total_links' => count($articleLinks),
                'processed' => $processedCount,
                'articles_created' => count($articles)
            ]);

            // If no articles were created, create a fallback
            if (empty($articles)) {
                Log::warning('CNN News: No articles created from links, creating fallback');
                $titleElement = $dom->find('title', 0);
                $pageTitle = $this->extractText($titleElement) ?: 'CNN News';

                $articles[] = $this->createArticle(
                    $pageTitle,
                    $source->base_url,
                    mb_substr(strip_tags($html), 0, 2000),
                    null,
                    null,
                    ['source' => 'cnn', 'fallback' => true, 'reason' => 'no_articles_created']
                );
            }

        } catch (\Exception $e) {
            Log::error('CNN News parsing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Create a fallback article even if parsing fails
            if (empty($articles)) {
                $articles[] = $this->createArticle(
                    'CNN News - ' . $source->name,
                    $source->base_url,
                    mb_substr(strip_tags($html), 0, 2000) ?: 'Content unavailable',
                    null,
                    null,
                    ['source' => 'cnn', 'fallback' => true, 'parse_error' => $e->getMessage()]
                );
            }
        } finally {
            if ($dom) {
                $this->cleanupDom($dom);
            }
        }

        return $articles;
    }
}

