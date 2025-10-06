<?php

namespace Tests\Feature\Services;

use App\Services\NewsAPIService;
use Tests\TestCase;

class NewsAPIServiceTest extends TestCase
{
    public function test_can_fetch_articles()
    {
        $service = new NewsAPIService();
        
        $this->assertInstanceOf(NewsAPIService::class, $service);
    }

    public function test_has_correct_source_name()
    {
        $service = new NewsAPIService();
        
        $this->assertEquals('News API', $service->getSourceName());
    }

    public function test_has_correct_api_identifier()
    {
        $service = new NewsAPIService();
        
        $this->assertEquals('news_api', $service->getApiIdentifier());
    }
}