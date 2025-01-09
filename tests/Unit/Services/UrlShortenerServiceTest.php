<?php

namespace Tests\Unit\Services;

use App\Exceptions\ShortUrlNotFound;
use App\Repositories\UrlRepositoryPipeline;
use App\Services\UrlShortenerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UrlShortenerServiceTest extends TestCase
{
    use RefreshDatabase;

    private UrlShortenerService $service;

    private UrlRepositoryPipeline $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(UrlRepositoryPipeline::class);
        $this->service = new UrlShortenerService($this->repository);

        // clear caches
        Cache::store('redis')->clear();
        Cache::store('octane')->clear();
    }

    public function test_encode_creates_new_short_url(): void
    {
        $url = 'https://example.com/very/long/url';

        $shortUrl = $this->service->encode($url);

        $this->assertDatabaseHas('short_urls', [
            'original_url' => $url,
        ]);
        $this->assertStringContainsString('/s/', $shortUrl);
    }

    public function test_encode_returns_existing_url(): void
    {
        $url = 'https://example.com/very/long/url';

        $firstShortUrl = $this->service->encode($url);
        $secondShortUrl = $this->service->encode($url);

        $this->assertEquals($firstShortUrl, $secondShortUrl);
    }

    public function test_decode_returns_original_url(): void
    {
        $url = 'https://example.com/very/long/url';
        $shortUrl = $this->service->encode($url);

        $decodedUrl = $this->service->decode($shortUrl);

        $this->assertEquals($url, $decodedUrl);
    }

    public function test_decode_throws_exception_for_invalid_url(): void
    {
        $this->expectException(ShortUrlNotFound::class);

        $this->service->decode('http://app.test/s/invalid');
    }
}
