<?php

namespace App\Repositories\Contracts;

use App\Models\CrawlJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CrawlJobRepositoryInterface
{
    public function all(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function find(int $id): ?CrawlJob;
    public function findOrFail(int $id): CrawlJob;
    public function create(array $data): CrawlJob;
    public function update(int $id, array $data): CrawlJob;
    public function delete(int $id): bool;
    public function findByStatus(string $status): Collection;
    public function findByCampaign(int $campaignId): Collection;
    public function findBySource(int $sourceId): Collection;
}

