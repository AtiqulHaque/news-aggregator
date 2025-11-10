<?php

namespace App\Services;

use App\Models\CampaignSource;
use App\Repositories\Contracts\CampaignSourceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CampaignSourceService
{
    public function __construct(
        private CampaignSourceRepositoryInterface $repository
    ) {
    }

    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    public function getById(int $id): CampaignSource
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data): CampaignSource
    {
        $this->validateData($data);
        
        // Check if association already exists
        $existing = $this->repository->findCampaignSource(
            $data['campaign_id'],
            $data['source_id']
        );
        
        if ($existing) {
            throw ValidationException::withMessages([
                'source_id' => ['This source is already associated with this campaign.'],
            ]);
        }
        
        return $this->repository->create($data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getByCampaign(int $campaignId): Collection
    {
        return $this->repository->findByCampaign($campaignId);
    }

    public function getBySource(int $sourceId): Collection
    {
        return $this->repository->findBySource($sourceId);
    }

    public function attachSourceToCampaign(int $campaignId, int $sourceId): CampaignSource
    {
        return $this->create([
            'campaign_id' => $campaignId,
            'source_id' => $sourceId,
        ]);
    }

    public function detachSourceFromCampaign(int $campaignId, int $sourceId): bool
    {
        $campaignSource = $this->repository->findCampaignSource($campaignId, $sourceId);
        if (!$campaignSource) {
            return false;
        }
        return $this->repository->delete($campaignSource->id);
    }

    private function validateData(array $data): void
    {
        $rules = [
            'campaign_id' => 'required|integer|exists:campaigns,id',
            'source_id' => 'required|integer|exists:news_sources,id',
        ];

        $validator = validator($data, $rules);
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }
}

