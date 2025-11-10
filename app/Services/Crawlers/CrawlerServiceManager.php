<?php

namespace App\Services\Crawlers;

use App\Models\NewsSource;
use App\Services\Crawlers\Contracts\NewsCrawlerInterface;
use Illuminate\Support\Collection;

class CrawlerServiceManager
{
    /**
     * Registered crawlers.
     *
     * @var Collection<int, NewsCrawlerInterface>
     */
    private Collection $crawlers;

    public function __construct()
    {
        $this->crawlers = collect();
    }

    /**
     * Register a crawler service.
     */
    public function register(NewsCrawlerInterface $crawler): void
    {
        $this->crawlers->push($crawler);
    }

    /**
     * Get all registered crawlers.
     *
     * @return Collection<int, NewsCrawlerInterface>
     */
    public function getAllCrawlers(): Collection
    {
        return $this->crawlers;
    }

    /**
     * Get the appropriate crawler for a news source.
     *
     * @param NewsSource $source
     * @return NewsCrawlerInterface|\Illuminate\Support\TFirstDefault|\Illuminate\Support\TValue
     * @throws \RuntimeException If no crawler supports the source
     */
    public function getCrawlerForSource(NewsSource $source)
    {
        // Find all crawlers that support this source
        $supportedCrawlers = $this->crawlers
            ->filter(fn (NewsCrawlerInterface $crawler) => $crawler->supports($source))
            ->sortByDesc(fn (NewsCrawlerInterface $crawler) => $crawler->getPriority());

        if ($supportedCrawlers->isEmpty()) {
            throw new \RuntimeException(
                "No crawler found that supports source: {$source->name} (ID: {$source->id})"
            );
        }

        // Return the highest priority crawler
        return $supportedCrawlers->first();
    }

    /**
     * Get crawler by name.
     *
     * @param string $name
     * @return NewsCrawlerInterface|\Illuminate\Support\TFirstDefault|\Illuminate\Support\TValue|null
     */
    public function getCrawlerByName(string $name)
    {
        return $this->crawlers->first(
            fn (NewsCrawlerInterface $crawler) => $crawler->getName() === $name
        );
    }
}

