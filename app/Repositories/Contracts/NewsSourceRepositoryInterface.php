<?php

namespace App\Repositories\Contracts;

use App\Models\NewsSource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface NewsSourceRepositoryInterface
{
    public function all(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function find(int $id): ?NewsSource;
    public function findOrFail(int $id): NewsSource;
    public function create(array $data): NewsSource;
    public function update(int $id, array $data): NewsSource;
    public function delete(int $id): bool;
    public function findByType(string $type): Collection;
    public function findActive(): Collection;
}

