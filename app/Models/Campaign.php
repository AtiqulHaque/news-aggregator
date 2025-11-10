<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Campaign",
    type: "object",
    description: "Campaign model",
    properties: [
        new OA\Property(property: "id", type: "integer", format: "int64", example: 1, description: "Campaign ID"),
        new OA\Property(property: "name", type: "string", maxLength: 255, example: "Summer Sale Campaign", description: "Campaign name"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "A promotional campaign for summer products", description: "Campaign description"),
        new OA\Property(property: "start_date", type: "string", format: "date-time", example: "2024-06-01T00:00:00Z", description: "Campaign start date and time"),
        new OA\Property(property: "end_date", type: "string", format: "date-time", nullable: true, example: "2024-08-31T23:59:59Z", description: "Campaign end date and time"),
        new OA\Property(property: "frequency_minutes", type: "integer", example: 1440, description: "Frequency in minutes (default: 1440 = daily)"),
        new OA\Property(property: "status", type: "string", enum: ["scheduled", "running", "completed", "failed"], example: "scheduled", description: "Campaign status"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z", description: "Creation timestamp"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z", description: "Last update timestamp"),
    ]
)]
class Campaign extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'frequency_minutes',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'frequency_minutes' => 'integer',
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
        return ['scheduled', 'running', 'completed', 'failed'];
    }

    /**
     * Get the news sources for this campaign.
     */
    public function sources(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(NewsSource::class, 'campaign_sources', 'campaign_id', 'source_id')
            ->withTimestamps();
    }

    /**
     * Get the campaign source associations.
     */
    public function campaignSources(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CampaignSource::class, 'campaign_id');
    }

    /**
     * Get the crawl jobs for this campaign.
     */
    public function crawlJobs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CrawlJob::class, 'campaign_id');
    }
}

