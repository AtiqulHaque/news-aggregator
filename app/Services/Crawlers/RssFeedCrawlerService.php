<?php

namespace App\Services\Crawlers;

use App\Models\NewsSource;

class RssFeedCrawlerService extends AbstractCrawlerService
{
    protected int $priority = 80; // Medium-high priority for RSS feeds

    public function getName(): string
    {
        return 'RSS Feed Crawler';
    }

    public function supports(NewsSource $source): bool
    {
        return $source->source_type === 'rss';
    }

    public function crawl(NewsSource $source): array
    {
        $articles = [];

        try {
            $this->log('info', 'Starting RSS feed crawl', ['source_id' => $source->id]);

            // Try common RSS feed paths
            $rssPaths = ['/feed', '/rss', '/rss.xml', '/feed.xml', '/atom.xml'];
            $xml = null;

            foreach ($rssPaths as $path) {
                try {
                    $url = rtrim($source->base_url, '/') . $path;
                    $xml = $this->fetchHtml($url);
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!$xml) {
                throw new \Exception("Failed to fetch RSS feed from {$source->base_url}");
            }

            $articles = $this->parseRssFeed($xml, $source);

            $this->log('info', 'RSS feed crawl completed', [
                'source_id' => $source->id,
                'articles_count' => count($articles),
            ]);
        } catch (\Exception $e) {
            $this->log('error', 'RSS feed crawl failed', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $articles;
    }

    /**
     * Parse RSS/Atom feed XML.
     */
    private function parseRssFeed(string $xml, NewsSource $source): array
    {
        $articles = [];
        $xmlObj = simplexml_load_string($xml);

        if ($xmlObj === false) {
            throw new \Exception("Failed to parse RSS XML");
        }

        // Handle RSS format
        if (isset($xmlObj->channel)) {
            $items = $xmlObj->channel->item ?? [];
            foreach ($items as $item) {
                $title = (string) ($item->title ?? 'Untitled');
                $url = (string) ($item->link ?? $source->base_url);
                $content = (string) ($item->description ?? '');
                $author = isset($item->author) ? (string) $item->author : null;
                $publishedAt = isset($item->pubDate) ? date('Y-m-d H:i:s', strtotime((string) $item->pubDate)) : null;

                $articles[] = $this->createArticle(
                    $title,
                    $url,
                    $content,
                    $author,
                    $publishedAt,
                    ['feed_type' => 'rss', 'guid' => (string) ($item->guid ?? null)]
                );
            }
        }
        // Handle Atom format
        elseif (isset($xmlObj->entry)) {
            foreach ($xmlObj->entry as $entry) {
                $title = (string) ($entry->title ?? 'Untitled');
                $url = (string) ($entry->link['href'] ?? $source->base_url);
                $content = (string) ($entry->content ?? $entry->summary ?? '');
                $author = isset($entry->author->name) ? (string) $entry->author->name : null;
                $publishedAt = isset($entry->published) ? date('Y-m-d H:i:s', strtotime((string) $entry->published)) : null;

                $articles[] = $this->createArticle(
                    $title,
                    $url,
                    $content,
                    $author,
                    $publishedAt,
                    ['feed_type' => 'atom', 'id' => (string) ($entry->id ?? null)]
                );
            }
        }

        return $articles;
    }
}

