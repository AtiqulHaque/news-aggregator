<?php

namespace App\Services\Crawlers;

use App\Models\NewsSource;

class BbcNewsCrawlerService extends AbstractCrawlerService
{
    protected int $priority = 100; // High priority for BBC-specific crawler

    public function getName(): string
    {
        return 'BBC News Crawler';
    }

    public function supports(NewsSource $source): bool
    {
        $url = strtolower($source->base_url);
        return str_contains($url, 'bbc.com') || str_contains($url, 'bbc.co.uk');
    }

    public function crawl(NewsSource $source): array
    {
        $articles = [];

        try {
            $this->log('info', 'Starting BBC News crawl', ['source_id' => $source->id]);

            // Try RSS feed first
            $rssUrl = rtrim($source->base_url, '/') . '/feed';
            try {
                $html = $this->fetchHtml($rssUrl);
                $articles = $this->parseRssFeed($html, $source);
            } catch (\Exception $e) {
                $this->log('warning', 'RSS feed failed, trying website', ['error' => $e->getMessage()]);
                // Fall back to website crawling
                $html = $this->fetchHtml($source->base_url);
                $articles = $this->parseWebsite($html, $source);
            }

            $this->log('info', 'BBC News crawl completed', [
                'source_id' => $source->id,
                'articles_count' => count($articles),
            ]);
        } catch (\Exception $e) {
            $this->log('error', 'BBC News crawl failed', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $articles;
    }

    /**
     * Parse BBC RSS feed.
     */
    private function parseRssFeed(string $xml, NewsSource $source): array
    {
        $articles = [];
        $xmlObj = simplexml_load_string($xml);

        if ($xmlObj === false) {
            throw new \Exception("Failed to parse RSS XML");
        }

        $items = $xmlObj->channel->item ?? [];
        foreach ($items as $item) {
            $title = (string) ($item->title ?? 'Untitled');
            $url = (string) ($item->link ?? $source->base_url);
            $content = (string) ($item->description ?? '');
            $publishedAt = isset($item->pubDate) ? date('Y-m-d H:i:s', strtotime((string) $item->pubDate)) : null;

            $articles[] = $this->createArticle(
                $title,
                $url,
                $content,
                null,
                $publishedAt,
                ['feed_type' => 'rss', 'source' => 'bbc']
            );
        }

        return $articles;
    }

    /**
     * Parse BBC website HTML.
     */
    private function parseWebsite(string $html, NewsSource $source): array
    {
        $articles = [];
        $dom = $this->parseHtml($html);

        try {
            // BBC uses specific article selectors
            $articleElements = $dom->find('article, div[data-testid="card"], div[data-testid="story-card"]');

            if (empty($articleElements)) {
                // Try alternative BBC selectors
                $articleElements = $dom->find('div.gs-c-promo, div.qa-story');
            }

            foreach ($articleElements as $articleElement) {
                // Extract title
                $titleElement = $articleElement->find('h3, h2, .qa-story-headline, [data-testid="card-headline"]', 0);
                $title = $this->extractText($titleElement);

                // Extract link
                $linkElement = $articleElement->find('a', 0);
                $url = $source->base_url;
                if ($linkElement) {
                    $href = $this->extractAttribute($linkElement, 'href');
                    if ($href) {
                        $url = $this->resolveUrl($href, $source->base_url);
                    }
                }

                // Extract summary/description
                $summaryElement = $articleElement->find('p, .qa-story-summary, [data-testid="card-description"]', 0);
                $content = $this->extractText($summaryElement);

                if (empty($title) && empty($url)) {
                    continue;
                }

                if (empty($title)) {
                    $title = 'BBC News Article';
                }

                $articles[] = $this->createArticle(
                    $title,
                    $url,
                    $content,
                    null,
                    null,
                    ['source' => 'bbc', 'parsed_from' => 'website']
                );
            }

            // If no articles found, create a fallback entry
            if (empty($articles)) {
                $titleElement = $dom->find('title', 0);
                $pageTitle = $this->extractText($titleElement) ?: 'BBC News';

                $articles[] = $this->createArticle(
                    $pageTitle,
                    $source->base_url,
                    mb_substr(strip_tags($html), 0, 2000),
                    null,
                    null,
                    ['source' => 'bbc', 'fallback' => true]
                );
            }
        } finally {
            $this->cleanupDom($dom);
        }

        return $articles;
    }
}

