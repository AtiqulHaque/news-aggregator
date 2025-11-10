<?php

namespace App\Http\Controllers;

use App\Services\CampaignSourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Campaign Sources", description: "Campaign-source association management endpoints")]
class CampaignSourceController extends Controller
{
    public function __construct(
        private CampaignSourceService $campaignSourceService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/campaign-sources",
     *     summary="Get all campaign-source associations",
     *     tags={"Campaign Sources"},
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(): JsonResponse
    {
        $associations = $this->campaignSourceService->getAll();
        return response()->json(['data' => $associations]);
    }

    /**
     * @OA\Post(
     *     path="/campaign-sources",
     *     summary="Create campaign-source association",
     *     tags={"Campaign Sources"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="campaign_id", type="integer", example=1),
     *         @OA\Property(property="source_id", type="integer", example=1)
     *     )),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $association = $this->campaignSourceService->create($request->all());
            return response()->json(['message' => 'Campaign-source association created successfully', 'data' => $association], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/campaign-sources/{id}",
     *     summary="Get campaign-source association by ID",
     *     tags={"Campaign Sources"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $association = $this->campaignSourceService->getById($id);
            return response()->json(['data' => $association]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Campaign-source association not found'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/campaign-sources/{id}",
     *     summary="Delete campaign-source association",
     *     tags={"Campaign Sources"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->campaignSourceService->delete($id);
            return response()->json(['message' => 'Campaign-source association deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Campaign-source association not found'], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/campaigns/{campaignId}/sources",
     *     summary="Get sources for a campaign",
     *     tags={"Campaign Sources"},
     *     @OA\Parameter(name="campaignId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function getByCampaign(int $campaignId): JsonResponse
    {
        $sources = $this->campaignSourceService->getByCampaign($campaignId);
        return response()->json(['data' => $sources]);
    }

    /**
     * @OA\Get(
     *     path="/news-sources/{sourceId}/campaigns",
     *     summary="Get campaigns for a source",
     *     tags={"Campaign Sources"},
     *     @OA\Parameter(name="sourceId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function getBySource(int $sourceId): JsonResponse
    {
        $campaigns = $this->campaignSourceService->getBySource($sourceId);
        return response()->json(['data' => $campaigns]);
    }

    /**
     * @OA\Post(
     *     path="/campaigns/{campaignId}/sources/{sourceId}",
     *     summary="Attach source to campaign",
     *     tags={"Campaign Sources"},
     *     @OA\Parameter(name="campaignId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sourceId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function attach(int $campaignId, int $sourceId): JsonResponse
    {
        try {
            $association = $this->campaignSourceService->attachSourceToCampaign($campaignId, $sourceId);
            return response()->json(['message' => 'Source attached to campaign successfully', 'data' => $association], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/campaigns/{campaignId}/sources/{sourceId}",
     *     summary="Detach source from campaign",
     *     tags={"Campaign Sources"},
     *     @OA\Parameter(name="campaignId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sourceId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function detach(int $campaignId, int $sourceId): JsonResponse
    {
        $result = $this->campaignSourceService->detachSourceFromCampaign($campaignId, $sourceId);
        if ($result) {
            return response()->json(['message' => 'Source detached from campaign successfully']);
        }
        return response()->json(['message' => 'Association not found'], 404);
    }
}
