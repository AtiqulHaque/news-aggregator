<?php

namespace App\Repositories\Contracts;

use App\Models\CampaignSource;
use Illuminate\Database\Eloquent\Collection;

interface CampaignSourceRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?CampaignSource;
    public function findOrFail(int $id): CampaignSource;
    public function create(array $data): CampaignSource;
    public function delete(int $id): bool;
    public function findByCampaign(int $campaignId): Collection;
    public function findBySource(int $sourceId): Collection;
    public function findCampaignSource(int $campaignId, int $sourceId): ?CampaignSource;
}

