<?php

namespace App\Services;

use App\Models\CrawlJob;
use App\Repositories\Contracts\CrawlJobRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CrawlJobService
{
    public function __construct(
        private CrawlJobRepositoryInterface $repository
    ) {
    }

    public function getAllJobs(): Collection
    {
        return $this->repository->all();
    }

    public function getPaginatedJobs(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function getJobById(int $id): CrawlJob
    {
        return $this->repository->findOrFail($id);
    }

    public function createJob(array $data): CrawlJob
    {
        $this->validateJobData($data);
        return $this->repository->create($data);
    }

    public function updateJob(int $id, array $data): CrawlJob
    {
        $this->validateJobData($data, $id);
        return $this->repository->update($id, $data);
    }

    public function deleteJob(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getJobsByStatus(string $status): Collection
    {
        if (!in_array($status, CrawlJob::getValidStatuses(), true)) {
            throw ValidationException::withMessages(['status' => ['Invalid status value.']]);
        }
        return $this->repository->findByStatus($status);
    }

    public function getJobsByCampaign(int $campaignId): Collection
    {
        return $this->repository->findByCampaign($campaignId);
    }

    public function getJobsBySource(int $sourceId): Collection
    {
        return $this->repository->findBySource($sourceId);
    }

    private function validateJobData(array $data, ?int $id = null): void
    {
        $rules = [
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
            'source_id' => 'nullable|integer|exists:news_sources,id',
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date|after:started_at',
            'status' => 'nullable|string|in:' . implode(',', CrawlJob::getValidStatuses()),
            'total_articles' => 'nullable|integer|min:0',
            'error_message' => 'nullable|string',
        ];

        $validator = validator($data, $rules);
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }
}

