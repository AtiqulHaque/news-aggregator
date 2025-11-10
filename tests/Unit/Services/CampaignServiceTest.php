<?php

namespace Tests\Unit\Services;

use App\Models\Campaign;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use App\Services\CampaignService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class CampaignServiceTest extends TestCase
{
    use RefreshDatabase;

    private CampaignService $service;
    private CampaignRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(CampaignRepositoryInterface::class);
        $this->service = new CampaignService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_get_all_campaigns(): void
    {
        $campaigns = new Collection([
            Campaign::factory()->make(),
            Campaign::factory()->make(),
        ]);

        $this->repository
            ->shouldReceive('all')
            ->once()
            ->andReturn($campaigns);

        $result = $this->service->getAllCampaigns();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_can_get_paginated_campaigns(): void
    {
        $paginated = Mockery::mock(LengthAwarePaginator::class);

        $this->repository
            ->shouldReceive('paginate')
            ->once()
            ->with(15)
            ->andReturn($paginated);

        $result = $this->service->getPaginatedCampaigns(15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_can_get_campaign_by_id(): void
    {
        $campaign = Campaign::factory()->make(['id' => 1]);

        $this->repository
            ->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($campaign);

        $result = $this->service->getCampaignById(1);

        $this->assertInstanceOf(Campaign::class, $result);
        $this->assertEquals(1, $result->id);
    }

    public function test_can_create_campaign(): void
    {
        $data = [
            'name' => 'Test Campaign',
            'description' => 'Test Description',
            'start_date' => now()->toDateTimeString(),
            'end_date' => now()->addDays(30)->toDateTimeString(),
            'frequency_minutes' => 1440,
            'status' => 'scheduled',
        ];

        $campaign = Campaign::factory()->make($data);

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) use ($data) {
                return $arg['name'] === $data['name']
                    && $arg['description'] === $data['description'];
            }))
            ->andReturn($campaign);

        $result = $this->service->createCampaign($data);

        $this->assertInstanceOf(Campaign::class, $result);
    }

    public function test_create_campaign_throws_validation_exception_for_invalid_data(): void
    {
        $data = [
            'name' => '', // Invalid: required
            'start_date' => 'invalid-date', // Invalid: must be date
        ];

        $this->expectException(ValidationException::class);

        $this->service->createCampaign($data);
    }

    public function test_can_update_campaign(): void
    {
        $campaign = Campaign::factory()->make(['id' => 1]);
        $data = [
            'name' => 'Updated Campaign',
        ];

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with(1, Mockery::on(function ($arg) use ($data) {
                return $arg['name'] === $data['name'];
            }))
            ->andReturn($campaign);

        $result = $this->service->updateCampaign(1, $data);

        $this->assertInstanceOf(Campaign::class, $result);
    }

    public function test_update_campaign_throws_validation_exception_for_invalid_data(): void
    {
        $data = [
            'status' => 'invalid-status', // Invalid: not in allowed values
        ];

        $this->expectException(ValidationException::class);

        $this->service->updateCampaign(1, $data);
    }

    public function test_can_delete_campaign(): void
    {
        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);

        $result = $this->service->deleteCampaign(1);

        $this->assertTrue($result);
    }

    public function test_can_get_campaigns_by_status(): void
    {
        $campaigns = new Collection([
            Campaign::factory()->make(['status' => 'scheduled']),
            Campaign::factory()->make(['status' => 'scheduled']),
        ]);

        $this->repository
            ->shouldReceive('findByStatus')
            ->once()
            ->with('scheduled')
            ->andReturn($campaigns);

        $result = $this->service->getCampaignsByStatus('scheduled');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_get_campaigns_by_status_throws_exception_for_invalid_status(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->getCampaignsByStatus('invalid-status');
    }
}

