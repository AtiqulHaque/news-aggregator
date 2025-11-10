<?php

namespace App\Http\Controllers;

use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Campaigns", description: "Campaign management endpoints")]
class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $campaignService
    ) {
    }

    /**
     * Display a listing of campaigns.
     *
     * @OA\Get(
     *     path="/campaigns",
     *     summary="Get a paginated list of campaigns",
     *     description="Retrieve a paginated list of all campaigns. You can control the number of items per page using the per_page parameter.",
     *     tags={"Campaigns"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page (default: 15)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Campaign")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="last_page", type="integer", example=7)
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $campaigns = $this->campaignService->getPaginatedCampaigns($perPage);

        return response()->json([
            'data' => $campaigns->items(),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
                'last_page' => $campaigns->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created campaign.
     *
     * @OA\Post(
     *     path="/campaigns",
     *     summary="Create a new campaign",
     *     description="Create a new campaign with the provided data. All required fields must be provided.",
     *     tags={"Campaigns"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "start_date"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Summer Sale Campaign", description="Campaign name"),
     *             @OA\Property(property="description", type="string", nullable=true, example="A promotional campaign for summer products", description="Campaign description"),
     *             @OA\Property(property="start_date", type="string", format="date-time", example="2024-06-01T00:00:00Z", description="Campaign start date and time"),
     *             @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example="2024-08-31T23:59:59Z", description="Campaign end date and time (must be after start_date)"),
     *             @OA\Property(property="frequency_minutes", type="integer", minimum=1, default=1440, example=1440, description="Frequency in minutes (default: 1440 = daily)"),
     *             @OA\Property(property="status", type="string", enum={"scheduled", "running", "completed", "failed"}, default="scheduled", example="scheduled", description="Campaign status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Campaign created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campaign created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Campaign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required.")),
     *                 @OA\Property(property="start_date", type="array", @OA\Items(type="string", example="The start date field is required."))
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $campaign = $this->campaignService->createCampaign($request->all());

            return response()->json([
                'message' => 'Campaign created successfully',
                'data' => $campaign,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified campaign.
     *
     * @OA\Get(
     *     path="/campaigns/{id}",
     *     summary="Get a specific campaign",
     *     description="Retrieve detailed information about a specific campaign by its ID.",
     *     tags={"Campaigns"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Campaign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Campaign not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campaign not found")
     *         )
     *     )
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $campaign = $this->campaignService->getCampaignById($id);

            return response()->json([
                'data' => $campaign,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Campaign not found',
            ], 404);
        }
    }

    /**
     * Update the specified campaign.
     *
     * @OA\Put(
     *     path="/campaigns/{id}",
     *     summary="Update a campaign",
     *     description="Update an existing campaign. Only provided fields will be updated.",
     *     tags={"Campaigns"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Updated Campaign Name", description="Campaign name"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Updated description", description="Campaign description"),
     *             @OA\Property(property="start_date", type="string", format="date-time", example="2024-06-01T00:00:00Z", description="Campaign start date and time"),
     *             @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example="2024-08-31T23:59:59Z", description="Campaign end date and time"),
     *             @OA\Property(property="frequency_minutes", type="integer", minimum=1, example=1440, description="Frequency in minutes"),
     *             @OA\Property(property="status", type="string", enum={"scheduled", "running", "completed", "failed"}, example="running", description="Campaign status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campaign updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Campaign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Campaign not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campaign not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $campaign = $this->campaignService->updateCampaign($id, $request->all());

            return response()->json([
                'message' => 'Campaign updated successfully',
                'data' => $campaign,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Campaign not found',
            ], 404);
        }
    }

    /**
     * Remove the specified campaign.
     *
     * @OA\Delete(
     *     path="/campaigns/{id}",
     *     summary="Delete a campaign",
     *     description="Permanently delete a campaign by its ID.",
     *     tags={"Campaigns"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campaign deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Campaign not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campaign not found")
     *         )
     *     )
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->campaignService->deleteCampaign($id);

            return response()->json([
                'message' => 'Campaign deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Campaign not found',
            ], 404);
        }
    }

    /**
     * Get campaigns by status.
     *
     * @OA\Get(
     *     path="/campaigns/status/{status}",
     *     summary="Get campaigns by status",
     *     description="Retrieve all campaigns that match the specified status.",
     *     tags={"Campaigns"},
     *     @OA\Parameter(
     *         name="status",
     *         in="path",
     *         description="Campaign status",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             enum={"scheduled", "running", "completed", "failed"},
     *             example="running"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Campaign"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid status value",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="status", type="array", @OA\Items(type="string", example="Invalid status value."))
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @param string $status
     * @return JsonResponse
     */
    public function getByStatus(Request $request, string $status): JsonResponse
    {
        try {
            $campaigns = $this->campaignService->getCampaignsByStatus($status);

            return response()->json([
                'data' => $campaigns,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}

