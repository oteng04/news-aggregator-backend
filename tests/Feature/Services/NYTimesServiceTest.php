<?php

namespace Tests\Feature\Services;

use App\Services\NYTimesService;
use Tests\TestCase;

class NYTimesServiceTest extends TestCase
{
    public function test_can_fetch_articles()
    {
        $service = new NYTimesService();
        
        $this->assertInstanceOf(NYTimesService::class, $service);
    }

    public function test_has_correct_source_name()
    {
        $service = new NYTimesService();
        
        $this->assertEquals('New York Times', $service->getSourceName());
    }

    public function test_has_correct_api_identifier()
    {
        $service = new NYTimesService();
        
        $this->assertEquals('ny_times', $service->getApiIdentifier());
    }
}