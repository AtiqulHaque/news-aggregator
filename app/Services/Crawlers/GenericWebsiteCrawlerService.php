<?php

namespace App\Services\Crawlers;

use App\Models\NewsSource;

class GenericWebsiteCrawlerService extends AbstractCrawlerService
{
    protected int $priority = 10; // Low priority - fallback crawler

    public function getName(): string
    {
        return 'Generic Website Crawler';
    }

    public function supports(NewsSource $source): bool
    {
        return $source->source_type === 'website';
    }

    public function crawl(NewsSource $source): array
    {
        $articles = [];

        try {
            $this->log('info', 'Starting generic website crawl', ['source_id' => $source->id]);

            $html = $this->fetchHtml($source->base_url);
            $dom = $this->parseHtml($html);

            try {
                // Try to find articles using common selectors
                $articleElements = null;
                $selectors = [
                    'article',
                    'div.article',
                    'div[class*="article"]',
                    'div.post',
                    'div[class*="post"]',
                    'div.entry',
                    'div[class*="entry"]',
                    'div.item',
                    'div[class*="item"]',
                ];

                foreach ($selectors as $selector) {
                    $articleElements = $dom->find($selector);
                    if (!empty($articleElements)) {
                        break;
                    }
                }

                // If no articles found, try to find any links that might be articles
                if (empty($articleElements)) {
                    $links = $dom->find('a[href*="/article"], a[href*="/post"], a[href*="/news"], a[href*="/story"]');
                    if (!empty($links)) {
                        foreach ($links as $link) {
                            $title = trim($link->plaintext ?? '');
                            $url = $link->href ?? '';

                            if (empty($title) || empty($url)) {
                                continue;
                            }

                            $url = $this->resolveUrl($url, $source->base_url);

                            // Try to find parent element for content
                            $parent = $link->parent();
                            $content = '';
                            if ($parent) {
                                $content = trim($parent->plaintext ?? '');
                            }

                            $articles[] = $this->createArticle(
                                $title,
                                $url,
                                mb_substr($content, 0, 2000),
                                null,
                                null,
                                ['extracted_from' => 'link']
                            );
                        }
                    }
                } else {
                    // Process found article elements
                    foreach ($articleElements as $articleElement) {
                        // Extract title - try multiple selectors
                        $title = '';
                        $titleSelectors = ['h1', 'h2', 'h3', '.title', '[class*="title"]', 'a.title'];
                        foreach ($titleSelectors as $titleSelector) {
                            $titleElement = $articleElement->find($titleSelector, 0);
                            if ($titleElement) {
                                $title = $this->extractText($titleElement);
                                if (!empty($title)) {
                                    break;
                                }
                            }
                        }

                        // If no title found, try getting text from first heading
                        if (empty($title)) {
                            $heading = $articleElement->find('h1, h2, h3', 0);
                            if ($heading) {
                                $title = $this->extractText($heading);
                            }
                        }

                        // Extract URL - look for links
                        $url = $source->base_url;
                        $linkElement = $articleElement->find('a', 0);
                        if ($linkElement) {
                            $href = $this->extractAttribute($linkElement, 'href');
                            if ($href) {
                                $url = $this->resolveUrl($href, $source->base_url);
                            }
                        }

                        // Extract content - try multiple selectors
                        $content = '';
                        $contentSelectors = ['.content', '.excerpt', '.summary', 'p', '[class*="content"]', '[class*="excerpt"]'];
                        foreach ($contentSelectors as $contentSelector) {
                            $contentElements = $articleElement->find($contentSelector);
                            if (!empty($contentElements)) {
                                $contentParts = [];
                                foreach ($contentElements as $contentEl) {
                                    $text = $this->extractText($contentEl);
                                    if (!empty($text)) {
                                        $contentParts[] = $text;
                                    }
                                }
                                $content = implode(' ', $contentParts);
                                if (!empty($content)) {
                                    break;
                                }
                            }
                        }

                        // If no content found, get all text from article element
                        if (empty($content)) {
                            $content = $this->extractText($articleElement);
                        }

                        // Extract author if available
                        $author = null;
                        $authorElement = $articleElement->find('.author, [class*="author"], .byline, [class*="byline"]', 0);
                        if ($authorElement) {
                            $author = $this->extractText($authorElement);
                        }

                        // Extract published date if available
                        $publishedAt = null;
                        $dateElement = $articleElement->find('time, .date, [class*="date"], .published, [class*="published"]', 0);
                        if ($dateElement) {
                            $dateStr = $this->extractAttribute($dateElement, 'datetime') ?? $this->extractText($dateElement);
                            if ($dateStr) {
                                try {
                                    $publishedAt = date('Y-m-d H:i:s', strtotime($dateStr));
                                } catch (\Exception $e) {
                                    // Ignore date parsing errors
                                }
                            }
                        }

                        // Skip if no title or URL
                        if (empty($title) && empty($url)) {
                            continue;
                        }

                        // Use source name as title if title is empty
                        if (empty($title)) {
                            $title = 'Article from ' . $source->name;
                        }

                        $articles[] = $this->createArticle(
                            $title,
                            $url,
                            mb_substr($content, 0, 5000),
                            $author,
                            $publishedAt
                        );
                    }
                }

                // If no articles found, create at least one entry with page content
                if (empty($articles)) {
                    $titleElement = $dom->find('title', 0);
                    $pageTitle = $this->extractText($titleElement) ?: 'Crawled from ' . $source->name;

                    $bodyElement = $dom->find('body', 0);
                    $pageContent = $this->extractText($bodyElement);

                    $articles[] = $this->createArticle(
                        $pageTitle,
                        $source->base_url,
                        mb_substr($pageContent, 0, 5000),
                        null,
                        now()->toDateTimeString(),
                        ['note' => 'No structured articles found, saved page content']
                    );
                }
            } finally {
                $this->cleanupDom($dom);
            }

            $this->log('info', 'Generic website crawl completed', [
                'source_id' => $source->id,
                'articles_count' => count($articles),
            ]);
        } catch (\Exception $e) {
            $this->log('error', 'Generic website crawl failed', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $articles;
    }
}

