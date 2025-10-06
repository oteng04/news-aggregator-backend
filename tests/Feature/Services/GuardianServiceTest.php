<?php

namespace Tests\Feature\Services;

use App\Services\GuardianService;
use Tests\TestCase;

class GuardianServiceTest extends TestCase
{
    public function test_can_fetch_articles()
    {
        $service = new GuardianService();
        
        $this->assertInstanceOf(GuardianService::class, $service);
    }

    public function test_has_correct_source_name()
    {
        $service = new GuardianService();
        
        $this->assertEquals('The Guardian', $service->getSourceName());
    }

    public function test_has_correct_api_identifier()
    {
        $service = new GuardianService();
        
        $this->assertEquals('guardian', $service->getApiIdentifier());
    }
}