<?php

namespace Tests\Feature;

use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_campaigns(): void
    {
        Campaign::factory()->count(5)->create();

        $response = $this->getJson('/api/campaigns');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'start_date',
                        'end_date',
                        'frequency_minutes',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    public function test_can_create_campaign(): void
    {
        $data = [
            'name' => 'New Campaign',
            'description' => 'Campaign Description',
            'start_date' => now()->toDateTimeString(),
            'end_date' => now()->addDays(30)->toDateTimeString(),
            'frequency_minutes' => 1440,
            'status' => 'scheduled',
        ];

        $response = $this->postJson('/api/campaigns', $data);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Campaign created successfully',
                'data' => [
                    'name' => 'New Campaign',
                    'description' => 'Campaign Description',
                ],
            ]);

        $this->assertDatabaseHas('campaigns', [
            'name' => 'New Campaign',
        ]);
    }

    public function test_create_campaign_validates_required_fields(): void
    {
        $response = $this->postJson('/api/campaigns', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'start_date']);
    }

    public function test_can_show_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        $response = $this->getJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                ],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_campaign(): void
    {
        $response = $this->getJson('/api/campaigns/999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Campaign not found',
            ]);
    }

    public function test_can_update_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'name' => 'Original Name',
        ]);

        $data = [
            'name' => 'Updated Name',
        ];

        $response = $this->putJson("/api/campaigns/{$campaign->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Campaign updated successfully',
                'data' => [
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_returns_404_for_nonexistent_campaign(): void
    {
        $response = $this->putJson('/api/campaigns/999', ['name' => 'Test']);

        $response->assertStatus(404);
    }

    public function test_can_delete_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        $response = $this->deleteJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Campaign deleted successfully',
            ]);

        $this->assertDatabaseMissing('campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_delete_returns_404_for_nonexistent_campaign(): void
    {
        $response = $this->deleteJson('/api/campaigns/999');

        $response->assertStatus(404);
    }

    public function test_can_get_campaigns_by_status(): void
    {
        Campaign::factory()->create(['status' => 'scheduled']);
        Campaign::factory()->create(['status' => 'running']);
        Campaign::factory()->create(['status' => 'scheduled']);

        $response = $this->getJson('/api/campaigns/status/scheduled');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_get_by_status_validates_status_value(): void
    {
        $response = $this->getJson('/api/campaigns/status/invalid-status');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}

