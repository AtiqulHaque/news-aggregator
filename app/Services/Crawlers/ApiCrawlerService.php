<?php

namespace App\Services\Crawlers;

use App\Models\NewsSource;
use Illuminate\Support\Facades\Http;

class ApiCrawlerService extends AbstractCrawlerService
{
    protected int $priority = 80; // Medium-high priority for API sources

    public function getName(): string
    {
        return 'API Crawler';
    }

    public function supports(NewsSource $source): bool
    {
        return $source->source_type === 'api';
    }

    public function crawl(NewsSource $source): array
    {
        $articles = [];

        try {
            $this->log('info', 'Starting API crawl', ['source_id' => $source->id]);

            $response = Http::timeout(30)->get($source->base_url);

            if (!$response->successful()) {
                throw new \Exception("API request failed with status: {$response->status()}");
            }

            $data = $response->json();

            // Handle different API response structures
            if (isset($data['articles'])) {
                $items = $data['articles'];
            } elseif (isset($data['results'])) {
                $items = $data['results'];
            } elseif (isset($data['data'])) {
                $items = $data['data'];
            } elseif (is_array($data) && isset($data[0])) {
                $items = $data;
            } else {
                throw new \Exception("Unexpected API response structure");
            }

            foreach ($items as $item) {
                $title = $item['title'] ?? $item['headline'] ?? 'Untitled';
                $url = $item['url'] ?? $item['link'] ?? $source->base_url;
                $content = $item['content'] ?? $item['body'] ?? $item['description'] ?? '';
                $author = $item['author'] ?? $item['byline'] ?? null;
                
                $publishedAt = null;
                if (isset($item['publishedAt'])) {
                    $publishedAt = date('Y-m-d H:i:s', strtotime($item['publishedAt']));
                } elseif (isset($item['published_at'])) {
                    $publishedAt = date('Y-m-d H:i:s', strtotime($item['published_at']));
                } elseif (isset($item['date'])) {
                    $publishedAt = date('Y-m-d H:i:s', strtotime($item['date']));
                }

                $articles[] = $this->createArticle(
                    $title,
                    $url,
                    $content,
                    $author,
                    $publishedAt,
                    ['source_type' => 'api', 'raw_data' => $item]
                );
            }

            $this->log('info', 'API crawl completed', [
                'source_id' => $source->id,
                'articles_count' => count($articles),
            ]);
        } catch (\Exception $e) {
            $this->log('error', 'API crawl failed', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $articles;
    }
}

