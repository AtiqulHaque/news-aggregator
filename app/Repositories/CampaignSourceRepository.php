<?php

namespace App\Repositories;

use App\Models\CampaignSource;
use App\Repositories\Contracts\CampaignSourceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CampaignSourceRepository implements CampaignSourceRepositoryInterface
{
    public function all(): Collection
    {
        return CampaignSource::all();
    }

    public function find(int $id): ?CampaignSource
    {
        return CampaignSource::find($id);
    }

    public function findOrFail(int $id): CampaignSource
    {
        return CampaignSource::findOrFail($id);
    }

    public function create(array $data): CampaignSource
    {
        return CampaignSource::create($data);
    }

    public function delete(int $id): bool
    {
        $campaignSource = $this->findOrFail($id);
        return $campaignSource->delete();
    }

    public function findByCampaign(int $campaignId): Collection
    {
        return CampaignSource::where('campaign_id', $campaignId)->get();
    }

    public function findBySource(int $sourceId): Collection
    {
        return CampaignSource::where('source_id', $sourceId)->get();
    }

    public function findCampaignSource(int $campaignId, int $sourceId): ?CampaignSource
    {
        return CampaignSource::where('campaign_id', $campaignId)
            ->where('source_id', $sourceId)
            ->first();
    }
}

