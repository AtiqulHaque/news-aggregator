<?php

namespace App\Models;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CrawlJob",
    type: "object",
    description: "Crawl job model",
    properties: [
        new OA\Property(property: "id", type: "integer", format: "int64", example: 1, description: "Crawl job ID"),
        new OA\Property(property: "campaign_id", type: "integer", format: "int64", nullable: true, example: 1, description: "Associated campaign ID"),
        new OA\Property(property: "source_id", type: "integer", format: "int64", nullable: true, example: 1, description: "Associated source ID"),
        new OA\Property(property: "started_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z", description: "Job start timestamp"),
        new OA\Property(property: "finished_at", type: "string", format: "date-time", nullable: true, example: "2024-01-01T01:00:00Z", description: "Job finish timestamp"),
        new OA\Property(property: "status", type: "string", enum: ["pending", "in_progress", "success", "failed"], example: "pending", description: "Job status"),
        new OA\Property(property: "total_articles", type: "integer", example: 0, description: "Total articles crawled"),
        new OA\Property(property: "error_message", type: "string", nullable: true, example: "Connection timeout", description: "Error message if failed"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z", description: "Creation timestamp"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z", description: "Last update timestamp"),
    ]
)]
class CrawlJob extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'source_id',
        'started_at',
        'finished_at',
        'status',
        'total_articles',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'campaign_id' => 'integer',
            'source_id' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_articles' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the valid status values.
     *
     * @return array<string>
     */
    public static function getValidStatuses(): array
    {
        return ['pending', 'in_progress', 'success', 'failed'];
    }

    /**
     * Get the campaign that owns this crawl job.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Get the news source for this crawl job.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class, 'source_id');
    }

    /**
     * Get the articles created by this crawl job.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'crawl_job_id');
    }
}
