<?php

namespace App\Repositories\Contracts;

use App\Models\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CampaignRepositoryInterface
{
    /**
     * Get all campaigns.
     *
     * @return Collection<int, Campaign>
     */
    public function all(): Collection;

    /**
     * Get paginated campaigns.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a campaign by ID.
     *
     * @param int $id
     * @return Campaign|null
     */
    public function find(int $id): ?Campaign;

    /**
     * Find a campaign by ID or fail.
     *
     * @param int $id
     * @return Campaign
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Campaign;

    /**
     * Create a new campaign.
     *
     * @param array<string, mixed> $data
     * @return Campaign
     */
    public function create(array $data): Campaign;

    /**
     * Update a campaign.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return Campaign
     */
    public function update(int $id, array $data): Campaign;

    /**
     * Delete a campaign.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get campaigns by status.
     *
     * @param string $status
     * @return Collection<int, Campaign>
     */
    public function findByStatus(string $status): Collection;
}

