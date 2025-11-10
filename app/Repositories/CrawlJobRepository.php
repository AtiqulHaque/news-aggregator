<?php

namespace App\Repositories;

use App\Models\CrawlJob;
use App\Repositories\Contracts\CrawlJobRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CrawlJobRepository implements CrawlJobRepositoryInterface
{
    public function all(): Collection
    {
        return CrawlJob::all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CrawlJob::paginate($perPage);
    }

    public function find(int $id): ?CrawlJob
    {
        return CrawlJob::find($id);
    }

    public function findOrFail(int $id): CrawlJob
    {
        return CrawlJob::findOrFail($id);
    }

    public function create(array $data): CrawlJob
    {
        return CrawlJob::create($data);
    }

    public function update(int $id, array $data): CrawlJob
    {
        $job = $this->findOrFail($id);
        $job->update($data);
        $job->refresh();
        return $job;
    }

    public function delete(int $id): bool
    {
        $job = $this->findOrFail($id);
        return $job->delete();
    }

    public function findByStatus(string $status): Collection
    {
        return CrawlJob::where('status', $status)->get();
    }

    public function findByCampaign(int $campaignId): Collection
    {
        return CrawlJob::where('campaign_id', $campaignId)->get();
    }

    public function findBySource(int $sourceId): Collection
    {
        return CrawlJob::where('source_id', $sourceId)->get();
    }
}

