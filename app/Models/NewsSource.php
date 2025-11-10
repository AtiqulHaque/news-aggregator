<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "NewsSource",
    type: "object",
    description: "News source model",
    properties: [
        new OA\Property(property: "id", type: "integer", format: "int64", example: 1, description: "News source ID"),
        new OA\Property(property: "name", type: "string", maxLength: 255, example: "BBC News", description: "Source name"),
        new OA\Property(property: "base_url", type: "string", example: "https://www.bbc.com", description: "Base URL of the source"),
        new OA\Property(property: "source_type", type: "string", enum: ["website", "rss", "api"], example: "rss", description: "Type of source", nullable: true),
        new OA\Property(property: "crawl_interval_minutes", type: "integer", example: 60, description: "Crawl interval in minutes"),
        new OA\Property(property: "is_active", type: "boolean", example: true, description: "Whether the source is active"),
        new OA\Property(property: "last_crawled_at", type: "string", format: "date-time", nullable: true, example: "2024-01-01T00:00:00Z", description: "Last crawl timestamp"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z", description: "Creation timestamp"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z", description: "Last update timestamp"),
    ]
)]
class NewsSource extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'base_url',
        'source_type',
        'crawl_interval_minutes',
        'is_active',
        'last_crawled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'crawl_interval_minutes' => 'integer',
            'is_active' => 'boolean',
            'last_crawled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the valid source type values.
     *
     * @return array<string>
     */
    public static function getValidSourceTypes(): array
    {
        return ['website', 'rss', 'api'];
    }

    /**
     * Get the campaigns that use this source.
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_sources', 'source_id', 'campaign_id')
            ->withTimestamps();
    }

    /**
     * Get the crawl jobs for this source.
     */
    public function crawlJobs(): HasMany
    {
        return $this->hasMany(CrawlJob::class, 'source_id');
    }
}
