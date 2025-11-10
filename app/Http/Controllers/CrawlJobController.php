<?php

namespace App\Http\Controllers;

use App\Services\CrawlJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Crawl Jobs", description: "Crawl job management endpoints")]
class CrawlJobController extends Controller
{
    public function __construct(
        private CrawlJobService $crawlJobService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/crawl-jobs",
     *     summary="Get paginated crawl jobs",
     *     tags={"Crawl Jobs"},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $jobs = $this->crawlJobService->getPaginatedJobs($perPage);
        return response()->json([
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/crawl-jobs",
     *     summary="Create crawl job",
     *     tags={"Crawl Jobs"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CrawlJob")),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $job = $this->crawlJobService->createJob($request->all());
            return response()->json(['message' => 'Crawl job created successfully', 'data' => $job], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/crawl-jobs/{id}",
     *     summary="Get crawl job by ID",
     *     tags={"Crawl Jobs"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $job = $this->crawlJobService->getJobById($id);
            return response()->json(['data' => $job]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Crawl job not found'], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/crawl-jobs/{id}",
     *     summary="Update crawl job",
     *     tags={"Crawl Jobs"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CrawlJob")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $job = $this->crawlJobService->updateJob($id, $request->all());
            return response()->json(['message' => 'Crawl job updated successfully', 'data' => $job]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Crawl job not found'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/crawl-jobs/{id}",
     *     summary="Delete crawl job",
     *     tags={"Crawl Jobs"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->crawlJobService->deleteJob($id);
            return response()->json(['message' => 'Crawl job deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Crawl job not found'], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/crawl-jobs/status/{status}",
     *     summary="Get crawl jobs by status",
     *     tags={"Crawl Jobs"},
     *     @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="string", enum={"pending", "in_progress", "success", "failed"})),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function getByStatus(string $status): JsonResponse
    {
        try {
            $jobs = $this->crawlJobService->getJobsByStatus($status);
            return response()->json(['data' => $jobs]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/crawl-jobs/campaign/{campaignId}",
     *     summary="Get crawl jobs by campaign",
     *     tags={"Crawl Jobs"},
     *     @OA\Parameter(name="campaignId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function getByCampaign(int $campaignId): JsonResponse
    {
        $jobs = $this->crawlJobService->getJobsByCampaign($campaignId);
        return response()->json(['data' => $jobs]);
    }

    /**
     * @OA\Get(
     *     path="/crawl-jobs/source/{sourceId}",
     *     summary="Get crawl jobs by source",
     *     tags={"Crawl Jobs"},
     *     @OA\Parameter(name="sourceId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function getBySource(int $sourceId): JsonResponse
    {
        $jobs = $this->crawlJobService->getJobsBySource($sourceId);
        return response()->json(['data' => $jobs]);
    }
}
