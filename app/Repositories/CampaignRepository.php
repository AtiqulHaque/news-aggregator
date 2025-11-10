<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CampaignRepository implements CampaignRepositoryInterface
{
    /**
     * Get all campaigns.
     *
     * @return Collection<int, Campaign>
     */
    public function all(): Collection
    {
        return Campaign::all();
    }

    /**
     * Get paginated campaigns.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Campaign::paginate($perPage);
    }

    /**
     * Find a campaign by ID.
     *
     * @param int $id
     * @return Campaign|null
     */
    public function find(int $id): ?Campaign
    {
        return Campaign::find($id);
    }

    /**
     * Find a campaign by ID or fail.
     *
     * @param int $id
     * @return Campaign
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Campaign
    {
        return Campaign::findOrFail($id);
    }

    /**
     * Create a new campaign.
     *
     * @param array<string, mixed> $data
     * @return Campaign
     */
    public function create(array $data): Campaign
    {
        return Campaign::create($data);
    }

    /**
     * Update a campaign.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return Campaign
     */
    public function update(int $id, array $data): Campaign
    {
        $campaign = $this->findOrFail($id);
        $campaign->update($data);
        $campaign->refresh();

        return $campaign;
    }

    /**
     * Delete a campaign.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $campaign = $this->findOrFail($id);

        return $campaign->delete();
    }

    /**
     * Get campaigns by status.
     *
     * @param string $status
     * @return Collection<int, Campaign>
     */
    public function findByStatus(string $status): Collection
    {
        return Campaign::where('status', $status)->get();
    }
}

