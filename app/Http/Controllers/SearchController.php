<?php

namespace App\Http\Controllers;

use App\Services\ElasticsearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Search", description: "Elasticsearch search and filter endpoints")]
class SearchController extends Controller
{
    public function __construct(
        private ElasticsearchService $elasticsearchService
    ) {
    }
    /**
     * Search articles in Elasticsearch.
     *
     * @OA\Get(
     *     path="/search",
     *     summary="Search articles",
     *     tags={"Search"},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query (searches in title, content, summary)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="author",
     *         in="query",
     *         description="Filter by author",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="source_id",
     *         in="query",
     *         description="Filter by source ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="campaign_id",
     *         in="query",
     *         description="Filter by campaign ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter articles from date (ISO 8601 format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter articles to date (ISO 8601 format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Results per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort field (_score for relevance, created_at for date)",
     *         required=false,
     *         @OA\Schema(type="string", default="_score")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Sort order (asc, desc)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total_pages", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Elasticsearch error")
     * )
     */
    public function search(Request $request): JsonResponse
    {
        try {
            // Get query parameters
            $params = [
                'q' => $request->input('q', ''),
                'author' => $request->input('author'),
                'source_id' => $request->input('source_id'),
                'campaign_id' => $request->input('campaign_id'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'page' => max(1, (int) $request->input('page', 1)),
                'per_page' => min(100, max(1, (int) $request->input('per_page', 20))),
                'sort' => $request->input('sort', '_score'), // Default to relevance score
                'order' => $request->input('order', 'desc'),
            ];

            // Build and execute search query
            $esQuery = $this->elasticsearchService->buildSearchQuery($params);
            $results = $this->elasticsearchService->search($esQuery);

            // Format and return results
            $formatted = $this->elasticsearchService->formatSearchResults(
                $results,
                $params['page'],
                $params['per_page']
            );

            return response()->json($formatted);

        } catch (\Exception $e) {
            Log::error('Exception during Elasticsearch search', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Search error',
                'message' => 'Unable to search articles. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get search suggestions/autocomplete.
     *
     * @OA\Get(
     *     path="/api/search/suggest",
     *     summary="Get search suggestions",
     *     tags={"Search"},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query for suggestions",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search suggestions",
     *         @OA\JsonContent(
     *             @OA\Property(property="suggestions", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function suggest(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');

            if (empty($query)) {
                return response()->json(['suggestions' => []]);
            }

            // Simple prefix search for suggestions
            $esQuery = [
                'size' => 10,
                'query' => [
                    'prefix' => [
                        'title' => [
                            'value' => $query,
                            'boost' => 1.0,
                        ],
                    ],
                ],
                '_source' => ['title'],
            ];

            $results = $this->elasticsearchService->search($esQuery);
            $hits = $results['hits']['hits'] ?? [];

            $suggestions = array_map(function ($hit) {
                return $hit['_source']['title'] ?? '';
            }, $hits);

            return response()->json([
                'suggestions' => array_unique(array_filter($suggestions)),
            ]);

        } catch (\Exception $e) {
            Log::error('Exception during search suggestions', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['suggestions' => []], 200);
        }
    }

    /**
     * Get filter options (available authors, sources, etc.).
     *
     * @OA\Get(
     *     path="/api/search/filters",
     *     summary="Get available filter options",
     *     tags={"Search"},
     *     @OA\Response(
     *         response=200,
     *         description="Filter options",
     *         @OA\JsonContent(
     *             @OA\Property(property="authors", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="sources", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="campaigns", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function filters(): JsonResponse
    {
        try {
            $filterOptions = $this->elasticsearchService->getFilterOptions();
            return response()->json($filterOptions);
        } catch (\Exception $e) {
            Log::error('Exception during filter options retrieval', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'authors' => [],
                'sources' => [],
                'campaigns' => [],
            ], 200);
        }
    }
}

