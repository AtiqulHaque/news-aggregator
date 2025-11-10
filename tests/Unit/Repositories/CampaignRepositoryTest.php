<?php

namespace Tests\Unit\Repositories;

use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CampaignRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CampaignRepository();
    }

    public function test_can_create_campaign(): void
    {
        $data = [
            'name' => 'Test Campaign',
            'description' => 'Test Description',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'frequency_minutes' => 1440,
            'status' => 'scheduled',
        ];

        $campaign = $this->repository->create($data);

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals('Test Campaign', $campaign->name);
        $this->assertEquals('Test Description', $campaign->description);
        $this->assertEquals('scheduled', $campaign->status);
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Test Campaign',
        ]);
    }

    public function test_can_find_campaign_by_id(): void
    {
        $campaign = Campaign::factory()->create();

        $found = $this->repository->find($campaign->id);

        $this->assertInstanceOf(Campaign::class, $found);
        $this->assertEquals($campaign->id, $found->id);
    }

    public function test_find_returns_null_when_campaign_not_found(): void
    {
        $found = $this->repository->find(999);

        $this->assertNull($found);
    }

    public function test_can_find_or_fail_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        $found = $this->repository->findOrFail($campaign->id);

        $this->assertInstanceOf(Campaign::class, $found);
        $this->assertEquals($campaign->id, $found->id);
    }

    public function test_find_or_fail_throws_exception_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->findOrFail(999);
    }

    public function test_can_update_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'name' => 'Original Name',
        ]);

        $updated = $this->repository->update($campaign->id, [
            'name' => 'Updated Name',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        $result = $this->repository->delete($campaign->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_can_find_campaigns_by_status(): void
    {
        Campaign::factory()->create(['status' => 'scheduled']);
        Campaign::factory()->create(['status' => 'running']);
        Campaign::factory()->create(['status' => 'scheduled']);

        $scheduledCampaigns = $this->repository->findByStatus('scheduled');

        $this->assertCount(2, $scheduledCampaigns);
        $this->assertTrue($scheduledCampaigns->every(fn ($campaign) => $campaign->status === 'scheduled'));
    }

    public function test_can_get_all_campaigns(): void
    {
        Campaign::factory()->count(5)->create();

        $campaigns = $this->repository->all();

        $this->assertCount(5, $campaigns);
    }

    public function test_can_paginate_campaigns(): void
    {
        Campaign::factory()->count(20)->create();

        $paginated = $this->repository->paginate(10);

        $this->assertEquals(20, $paginated->total());
        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(2, $paginated->lastPage());
    }
}

