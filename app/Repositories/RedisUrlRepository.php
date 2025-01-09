<?php

namespace App\Repositories;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class RedisUrlRepository implements UrlRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    private Repository $redis;

    public function __construct()
    {
        $this->redis = Cache::store('redis');
    }

    public function encode(string $url): ?string
    {
        return $this->redis->get('url_encode:'.$url);
    }

    public function decode(string $code): ?string
    {
        return $this->redis->get('url_decode:'.$code);
    }

    public function save(string $url, string $code): void
    {
        $this->redis->put('url_encode:'.$url, $code, self::CACHE_TTL);
        $this->redis->put('url_decode:'.$code, $url, self::CACHE_TTL);
    }
}
