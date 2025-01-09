<?php

namespace Tests\Unit\Repositories;

use App\Repositories\SwooleUrlRepository;
use Exception;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SwooleUrlRepositoryTest extends TestCase
{
    private SwooleUrlRepository $repository;

    private Repository $swooleStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new SwooleUrlRepository;
        $this->swooleStore = Cache::store('octane');
        $this->swooleStore->clear();
    }

    public function test_encode_returns_cached_code(): void
    {
        $this->swooleStore->put('url_encode:https://example.com', 'abc123', 600);

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
        $this->swooleStore->put('url_decode:abc123', 'https://example.com', 600);

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
            $this->swooleStore->get('url_encode:https://example.com')
        );
        $this->assertEquals(
            'https://example.com',
            $this->swooleStore->get('url_decode:abc123')
        );
    }

    public function test_save_handles_full_table(): void
    {
        // Mock the swoole store to throw an exception on first attempt
        Cache::shouldReceive('store')
            ->with('octane')
            ->once()
            ->andReturn($mockStore = \Mockery::mock(Repository::class));

        $mockStore->shouldReceive('put')
            ->once()
            ->with('url_encode:https://example.com', 'abc123', 600)
            ->andThrow(new Exception('Table is full'));

        $mockStore->shouldReceive('clear')->once();

        $mockStore->shouldReceive('put')
            ->once()
            ->with('url_encode:https://example.com', 'abc123', 600)
            ->andReturn(true);

        $mockStore->shouldReceive('put')
            ->once()
            ->with('url_decode:abc123', 'https://example.com', 600)
            ->andReturn(true);

        $repository = new SwooleUrlRepository;

        // This should not throw an exception
        $repository->save('https://example.com', 'abc123');
    }
}
