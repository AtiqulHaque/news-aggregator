<?php

namespace App\Http\Controllers;

use App\Services\NewsSourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "News Sources", description: "News source management endpoints")]
class NewsSourceController extends Controller
{
    public function __construct(
        private NewsSourceService $newsSourceService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/news-sources",
     *     summary="Get paginated news sources",
     *     tags={"News Sources"},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/NewsSource")),
     *         @OA\Property(property="meta", type="object")
     *     ))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $sources = $this->newsSourceService->getPaginatedSources($perPage);
        return response()->json([
            'data' => $sources->items(),
            'meta' => [
                'current_page' => $sources->currentPage(),
                'per_page' => $sources->perPage(),
                'total' => $sources->total(),
                'last_page' => $sources->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/news-sources",
     *     summary="Create news source",
     *     tags={"News Sources"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/NewsSource")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", ref="#/components/schemas/NewsSource")
     *     )),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $source = $this->newsSourceService->createSource($request->all());
            return response()->json(['message' => 'News source created successfully', 'data' => $source], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/news-sources/{id}",
     *     summary="Get news source by ID",
     *     tags={"News Sources"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", ref="#/components/schemas/NewsSource")
     *     )),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $source = $this->newsSourceService->getSourceById($id);
            return response()->json(['data' => $source]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'News source not found'], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/news-sources/{id}",
     *     summary="Update news source",
     *     tags={"News Sources"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/NewsSource")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $source = $this->newsSourceService->updateSource($id, $request->all());
            return response()->json(['message' => 'News source updated successfully', 'data' => $source]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'News source not found'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/news-sources/{id}",
     *     summary="Delete news source",
     *     tags={"News Sources"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->newsSourceService->deleteSource($id);
            return response()->json(['message' => 'News source deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'News source not found'], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/news-sources/type/{type}",
     *     summary="Get news sources by type",
     *     tags={"News Sources"},
     *     @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string", enum={"website", "rss", "api"})),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function getByType(string $type): JsonResponse
    {
        try {
            $sources = $this->newsSourceService->getSourcesByType($type);
            return response()->json(['data' => $sources]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/news-sources/active/list",
     *     summary="Get active news sources",
     *     tags={"News Sources"},
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function getActive(): JsonResponse
    {
        $sources = $this->newsSourceService->getActiveSources();
        return response()->json(['data' => $sources]);
    }
}
