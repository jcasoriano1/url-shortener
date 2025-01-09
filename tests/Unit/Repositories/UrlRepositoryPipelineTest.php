<?php

namespace Tests\Unit\Repositories;

use App\Models\ShortUrl;
use App\Repositories\UrlRepositoryPipeline;
use App\Repositories\EloquentUrlRepository;
use App\Repositories\RedisUrlRepository;
use App\Repositories\SwooleUrlRepository;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UrlRepositoryPipelineTest extends TestCase
{
    use RefreshDatabase;

    private UrlRepositoryPipeline $repository;

    private SwooleUrlRepository $swooleRepository;

    private RedisUrlRepository $redisRepository;

    private EloquentUrlRepository $eloquentRepository;

    private Repository $swoole;

    private Repository $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->swooleRepository = new SwooleUrlRepository;
        $this->redisRepository = new RedisUrlRepository;
        $this->eloquentRepository = new EloquentUrlRepository;

        $this->swoole = Cache::store('octane');
        $this->redis = Cache::store('redis');

        $this->repository = new UrlRepositoryPipeline(
            $this->swooleRepository,
            $this->redisRepository,
            $this->eloquentRepository
        );

        // Clear all caches
        $this->swoole->clear();
        $this->redis->clear();
    }

    public function test_encode_checks_repositories_in_order_and_backfills(): void
    {
        $url = 'https://example.com';

        // Create entry only in database
        ShortUrl::create([
            'original_url' => $url,
            'code' => 'abc123',
        ]);

        // First request should find in database and backfill caches
        $this->assertEquals('abc123', $this->repository->encode($url));

        // Verify data was backfilled to Redis and Swoole
        $this->assertEquals('abc123', $this->redis->get('url_encode:'.$url));
        $this->assertEquals('abc123', $this->swoole->get('url_encode:'.$url));
    }

    public function test_decode_checks_repositories_in_order_and_backfills(): void
    {
        $code = 'abc123';
        $url = 'https://example.com';

        // Create entry only in database
        ShortUrl::create([
            'original_url' => $url,
            'code' => $code,
        ]);

        // First request should find in database and backfill caches
        $this->assertEquals($url, $this->repository->decode($code));

        // Verify data was backfilled to Redis and Swoole
        $this->assertEquals($url, $this->redis->get('url_decode:'.$code));
        $this->assertEquals($url, $this->swoole->get('url_decode:'.$code));
    }

    public function test_encode_backfills_from_redis_to_swoole(): void
    {
        $url = 'https://example.com';
        $code = 'abc123';

        // Create entry only in Redis
        $this->redis->put('url_encode:'.$url, $code, 3600);

        // First request should find in Redis and backfill Swoole
        $this->assertEquals($code, $this->repository->encode($url));

        // Verify data was backfilled to Swoole
        $this->assertEquals($code, $this->swoole->get('url_encode:'.$url));
    }

    public function test_decode_backfills_from_redis_to_swoole(): void
    {
        $code = 'abc123';
        $url = 'https://example.com';

        // Create entry only in Redis
        $this->redis->put('url_decode:'.$code, $url, 3600);

        // First request should find in Redis and backfill Swoole
        $this->assertEquals($url, $this->repository->decode($code));

        // Verify data was backfilled to Swoole
        $this->assertEquals($url, $this->swoole->get('url_decode:'.$code));
    }

    public function test_save_stores_in_reverse_order(): void
    {
        $url = 'https://example.com';
        $code = 'abc123';

        $this->repository->save($url, $code);

        // Check database
        $this->assertDatabaseHas('short_urls', [
            'original_url' => $url,
            'code' => $code,
        ]);

        // Check Redis
        $this->assertEquals($code, $this->redis->get('url_encode:'.$url));
        $this->assertEquals($url, $this->redis->get('url_decode:'.$code));

        // Check Swoole
        $this->assertEquals($code, $this->swoole->get('url_encode:'.$url));
        $this->assertEquals($url, $this->swoole->get('url_decode:'.$code));
    }

    public function test_returns_null_when_no_repository_has_value(): void
    {
        $this->assertNull($this->repository->encode('https://notfound.com'));
        $this->assertNull($this->repository->decode('notfound'));
    }
}
