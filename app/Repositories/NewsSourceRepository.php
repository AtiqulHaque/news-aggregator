<?php

namespace App\Repositories;

use App\Models\NewsSource;
use App\Repositories\Contracts\NewsSourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NewsSourceRepository implements NewsSourceRepositoryInterface
{
    public function all(): Collection
    {
        return NewsSource::all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return NewsSource::paginate($perPage);
    }

    public function find(int $id): ?NewsSource
    {
        return NewsSource::find($id);
    }

    public function findOrFail(int $id): NewsSource
    {
        return NewsSource::findOrFail($id);
    }

    public function create(array $data): NewsSource
    {
        return NewsSource::create($data);
    }

    public function update(int $id, array $data): NewsSource
    {
        $source = $this->findOrFail($id);
        $source->update($data);
        $source->refresh();
        return $source;
    }

    public function delete(int $id): bool
    {
        $source = $this->findOrFail($id);
        return $source->delete();
    }

    public function findByType(string $type): Collection
    {
        return NewsSource::where('source_type', $type)->get();
    }

    public function findActive(): Collection
    {
        return NewsSource::where('is_active', true)->get();
    }
}

