<?php

namespace App\Services;

use App\Models\Campaign;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CampaignService
{
    public function __construct(
        private CampaignRepositoryInterface $repository
    ) {
    }

    /**
     * Get all campaigns.
     *
     * @return Collection<int, Campaign>
     */
    public function getAllCampaigns(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Get paginated campaigns.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedCampaigns(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Get a campaign by ID.
     *
     * @param int $id
     * @return Campaign
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getCampaignById(int $id): Campaign
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Create a new campaign.
     *
     * @param array<string, mixed> $data
     * @return Campaign
     * @throws ValidationException
     */
    public function createCampaign(array $data): Campaign
    {
        $this->validateCampaignData($data);

        return $this->repository->create($data);
    }

    /**
     * Update a campaign.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return Campaign
     * @throws ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateCampaign(int $id, array $data): Campaign
    {
        $this->validateCampaignData($data, $id);

        return $this->repository->update($id, $data);
    }

    /**
     * Delete a campaign.
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteCampaign(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Get campaigns by status.
     *
     * @param string $status
     * @return Collection<int, Campaign>
     */
    public function getCampaignsByStatus(string $status): Collection
    {
        if (!in_array($status, Campaign::getValidStatuses(), true)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status value.'],
            ]);
        }

        return $this->repository->findByStatus($status);
    }

    /**
     * Validate campaign data.
     *
     * @param array<string, mixed> $data
     * @param int|null $id
     * @return void
     * @throws ValidationException
     */
    private function validateCampaignData(array $data, ?int $id = null): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'frequency_minutes' => 'nullable|integer|min:1',
            'status' => 'nullable|string|in:' . implode(',', Campaign::getValidStatuses()),
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }
}

