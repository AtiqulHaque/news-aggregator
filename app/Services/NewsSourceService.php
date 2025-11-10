<?php

namespace App\Services;

use App\Models\NewsSource;
use App\Repositories\Contracts\NewsSourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class NewsSourceService
{
    public function __construct(
        private NewsSourceRepositoryInterface $repository
    ) {
    }

    public function getAllSources(): Collection
    {
        return $this->repository->all();
    }

    public function getPaginatedSources(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function getSourceById(int $id): NewsSource
    {
        return $this->repository->findOrFail($id);
    }

    public function createSource(array $data): NewsSource
    {
        $this->validateSourceData($data);
        return $this->repository->create($data);
    }

    public function updateSource(int $id, array $data): NewsSource
    {
        $this->validateSourceData($data, $id);
        return $this->repository->update($id, $data);
    }

    public function deleteSource(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getSourcesByType(string $type): Collection
    {
        if (!in_array($type, NewsSource::getValidSourceTypes(), true)) {
            throw ValidationException::withMessages(['type' => ['Invalid source type.']]);
        }
        return $this->repository->findByType($type);
    }

    public function getActiveSources(): Collection
    {
        return $this->repository->findActive();
    }

    private function validateSourceData(array $data, ?int $id = null): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'base_url' => 'required|url',
            'source_type' => 'nullable|string|in:' . implode(',', NewsSource::getValidSourceTypes()),
            'crawl_interval_minutes' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'last_crawled_at' => 'nullable|date',
        ];

        $validator = validator($data, $rules);
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }
}

