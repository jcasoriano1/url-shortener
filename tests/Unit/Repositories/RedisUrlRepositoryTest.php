<?php

namespace Tests\Unit\Repositories;

use App\Repositories\RedisUrlRepository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RedisUrlRepositoryTest extends TestCase
{
    private RedisUrlRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RedisUrlRepository;
        Cache::store('redis')->clear();
    }

    public function test_encode_returns_cached_code(): void
    {
        Cache::store('redis')->put('url_encode:https://example.com', 'abc123', 3600);

        $result = $this->repository->encode('https://example.com');

        $this->assertEquals('abc123', $result);
    }

    public function test_encode_returns_null_for_cache_miss(): void
    {
        $result = $this->repository->encode('https://example.com');

        $this->assertNull($result);
    }

    public function test_decode_returns_cached_url(): void
    {
        Cache::store('redis')->put('url_decode:abc123', 'https://example.com', 3600);

        $result = $this->repository->decode('abc123');

        $this->assertEquals('https://example.com', $result);
    }

    public function test_decode_returns_null_for_cache_miss(): void
    {
        $result = $this->repository->decode('abc123');

        $this->assertNull($result);
    }

    public function test_save_stores_both_directions(): void
    {
        $this->repository->save('https://example.com', 'abc123');

        $this->assertEquals(
            'abc123',
            Cache::store('redis')->get('url_encode:https://example.com')
        );
        $this->assertEquals(
            'https://example.com',
            Cache::store('redis')->get('url_decode:abc123')
        );
    }
}
