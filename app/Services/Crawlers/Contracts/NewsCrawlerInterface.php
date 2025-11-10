<?php

namespace App\Services\Crawlers\Contracts;

use App\Models\NewsSource;

interface NewsCrawlerInterface
{
    /**
     * Get the name of this crawler.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Crawl the news source and extract articles.
     *
     * @param NewsSource $source
     * @return array<int, array<string, mixed>>
     */
    public function crawl(NewsSource $source): array;

    /**
     * Check if this crawler supports the given news source.
     *
     * @param NewsSource $source
     * @return bool
     */
    public function supports(NewsSource $source): bool;

    /**
     * Get the priority of this crawler (higher = preferred).
     *
     * @return int
     */
    public function getPriority(): int;
}

