<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
    private string $host;
    private int $port;
    private string $index;

    public function __construct()
    {
        $this->host = env('ELASTICSEARCH_HOST', 'elasticsearch');
        $this->port = env('ELASTICSEARCH_PORT', 9200);
        $this->index = 'articles';
    }

    /**
     * Get the base URL for Elasticsearch.
     */
    private function getBaseUrl(): string
    {
        return "http://{$this->host}:{$this->port}";
    }

    /**
     * Get the search URL.
     */
    private function getSearchUrl(): string
    {
        return "{$this->getBaseUrl()}/{$this->index}/_search";
    }

    /**
     * Execute a search query.
     */
    public function search(array $query): array
    {
        try {
            $response = Http::timeout(10)->post($this->getSearchUrl(), $query);

            if (!$response->successful()) {
                Log::error('Elasticsearch search failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'query' => $query,
                ]);
                throw new \Exception("Elasticsearch search failed: {$response->status()}");
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Exception during Elasticsearch search', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);
            throw $e;
        }
    }

    /**
     * Build a search query with filters.
     */
    public function buildSearchQuery(array $params): array
    {
        $query = $params['q'] ?? '';
        $author = $params['author'] ?? null;
        $sourceId = $params['source_id'] ?? null;
        $campaignId = $params['campaign_id'] ?? null;
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 20)));
        $sort = $params['sort'] ?? 'published_at';
        $order = $params['order'] ?? 'desc';

        $mustClauses = [];
        $filterClauses = [];

        // Full-text search query
        if (!empty($query)) {
            $mustClauses[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['title^3', 'content^1', 'summary^2', 'author'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ];
        } else {
            $mustClauses[] = ['match_all' => new \stdClass()];
        }

        // Author filter
        if (!empty($author)) {
            $filterClauses[] = [
                'match' => [
                    'author' => $author,
                ],
            ];
        }

        // Source ID filter
        if (!empty($sourceId)) {
            $filterClauses[] = [
                'term' => [
                    'source_id' => (int) $sourceId,
                ],
            ];
        }

        // Campaign ID filter
        if (!empty($campaignId)) {
            $filterClauses[] = [
                'term' => [
                    'campaign_id' => (int) $campaignId,
                ],
            ];
        }

            // Date range filter - use created_at as fallback if published_at not available
            if (!empty($dateFrom) || !empty($dateTo)) {
                $dateRange = [];
                if (!empty($dateFrom)) {
                    $dateRange['gte'] = $dateFrom;
                }
                if (!empty($dateTo)) {
                    $dateRange['lte'] = $dateTo;
                }
                // Try published_at first, fallback to created_at
                $filterClauses[] = [
                    'bool' => [
                        'should' => [
                            [
                                'range' => [
                                    'published_at' => $dateRange,
                                ],
                            ],
                            [
                                'range' => [
                                    'created_at' => $dateRange,
                                ],
                            ],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ];
            }

        // Build the complete query
        $esQuery = [
            'query' => [
                'bool' => [
                    'must' => $mustClauses,
                ],
            ],
            'size' => $perPage,
            'from' => ($page - 1) * $perPage,
        ];

        // Handle sorting - avoid published_at as it may not be mapped
        // Use _score for relevance or created_at for date sorting
        if ($sort === '_score' || !empty($query)) {
            // Sort by relevance when searching
            $esQuery['sort'] = [
                ['_score' => ['order' => 'desc']],
                ['created_at' => ['order' => $order, 'missing' => '_last']],
            ];
        } elseif ($sort === 'created_at') {
            // Sort by created_at
            $esQuery['sort'] = [
                ['created_at' => ['order' => $order, 'missing' => '_last']],
            ];
        } else {
            // Default: sort by created_at (most reliable field)
            $esQuery['sort'] = [
                ['created_at' => ['order' => $order, 'missing' => '_last']],
            ];
        }

        // Add filters if any
        if (!empty($filterClauses)) {
            $esQuery['query']['bool']['filter'] = $filterClauses;
        }

        return $esQuery;
    }

    /**
     * Format search results.
     */
    public function formatSearchResults(array $results, int $page, int $perPage): array
    {
        $hits = $results['hits'] ?? [];
        $total = $hits['total']['value'] ?? ($hits['total'] ?? 0);

        // Format results
        $articles = array_map(function ($hit) {
            return $hit['_source'];
        }, $hits['hits'] ?? []);

        return [
            'data' => $articles,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Delete articles from Elasticsearch by campaign ID.
     */
    public function deleteByCampaignId(int $campaignId): int
    {
        try {
            $query = [
                'query' => [
                    'term' => [
                        'campaign_id' => $campaignId,
                    ],
                ],
            ];

            $url = "{$this->getBaseUrl()}/{$this->index}/_delete_by_query";
            $response = Http::timeout(30)->post($url, $query);

            if (!$response->successful()) {
                Log::error('Failed to delete articles from Elasticsearch by campaign', [
                    'campaign_id' => $campaignId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return 0;
            }

            $result = $response->json();
            $deletedCount = $result['deleted'] ?? 0;

            Log::info('Deleted articles from Elasticsearch by campaign', [
                'campaign_id' => $campaignId,
                'deleted_count' => $deletedCount,
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Exception while deleting articles from Elasticsearch by campaign', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Delete articles from Elasticsearch by source ID.
     */
    public function deleteBySourceId(int $sourceId): int
    {
        try {
            $query = [
                'query' => [
                    'term' => [
                        'source_id' => $sourceId,
                    ],
                ],
            ];

            $url = "{$this->getBaseUrl()}/{$this->index}/_delete_by_query";
            $response = Http::timeout(30)->post($url, $query);

            if (!$response->successful()) {
                Log::error('Failed to delete articles from Elasticsearch by source', [
                    'source_id' => $sourceId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return 0;
            }

            $result = $response->json();
            $deletedCount = $result['deleted'] ?? 0;

            Log::info('Deleted articles from Elasticsearch by source', [
                'source_id' => $sourceId,
                'deleted_count' => $deletedCount,
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Exception while deleting articles from Elasticsearch by source', [
                'source_id' => $sourceId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get aggregations for filter options.
     */
    public function getFilterOptions(): array
    {
        try {
            // Get unique authors
            $authorsQuery = [
                'size' => 0,
                'aggs' => [
                    'unique_authors' => [
                        'terms' => [
                            'field' => 'author',
                            'size' => 100,
                            'missing' => 'N/A',
                        ],
                    ],
                ],
            ];

            // Get unique sources
            $sourcesQuery = [
                'size' => 0,
                'aggs' => [
                    'unique_sources' => [
                        'terms' => [
                            'field' => 'source_id',
                            'size' => 100,
                        ],
                        'aggs' => [
                            'source_names' => [
                                'terms' => [
                                    'field' => 'source_name',
                                    'size' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            // Get unique campaigns
            $campaignsQuery = [
                'size' => 0,
                'aggs' => [
                    'unique_campaigns' => [
                        'terms' => [
                            'field' => 'campaign_id',
                            'size' => 100,
                        ],
                        'aggs' => [
                            'campaign_names' => [
                                'terms' => [
                                    'field' => 'campaign_name',
                                    'size' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $authorsResponse = $this->search($authorsQuery);
            $sourcesResponse = $this->search($sourcesQuery);
            $campaignsResponse = $this->search($campaignsQuery);

            $authors = array_map(function ($bucket) {
                return $bucket['key'];
            }, $authorsResponse['aggregations']['unique_authors']['buckets'] ?? []);

            $sources = array_map(function ($bucket) {
                return [
                    'id' => $bucket['key'],
                    'name' => $bucket['source_names']['buckets'][0]['key'] ?? 'Unknown',
                    'count' => $bucket['doc_count'] ?? 0,
                ];
            }, $sourcesResponse['aggregations']['unique_sources']['buckets'] ?? []);

            $campaigns = array_map(function ($bucket) {
                return [
                    'id' => $bucket['key'],
                    'name' => $bucket['campaign_names']['buckets'][0]['key'] ?? 'Unknown',
                    'count' => $bucket['doc_count'] ?? 0,
                ];
            }, $campaignsResponse['aggregations']['unique_campaigns']['buckets'] ?? []);

            return [
                'authors' => array_filter($authors, fn($author) => $author !== 'N/A' && !empty($author)),
                'sources' => $sources,
                'campaigns' => $campaigns,
            ];
        } catch (\Exception $e) {
            Log::error('Exception during filter options retrieval', [
                'error' => $e->getMessage(),
            ]);
            return [
                'authors' => [],
                'sources' => [],
                'campaigns' => [],
            ];
        }
    }
}

