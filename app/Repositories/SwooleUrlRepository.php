<?php

namespace App\Repositories;

use Exception;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class SwooleUrlRepository implements UrlRepositoryInterface
{
    private const CACHE_TTL = 600; // 10 minutes

    private Repository $swoole;

    public function __construct()
    {
        $this->swoole = Cache::store('octane');
    }

    public function encode(string $url): ?string
    {
        return $this->swoole->get('url_encode:'.$url);
    }

    public function decode(string $code): ?string
    {
        return $this->swoole->get('url_decode:'.$code);
    }

    public function save(string $url, string $code): void
    {
        $swoole = $this->swoole;
        try {
            $swoole->put('url_encode:'.$url, $code, self::CACHE_TTL);
            $swoole->put('url_decode:'.$code, $url, self::CACHE_TTL);
        } catch (Exception $e) {
            // if Swoole table is full, clear it and try again
            $swoole->clear();
            $swoole->put('url_encode:'.$url, $code, self::CACHE_TTL);
            $swoole->put('url_decode:'.$code, $url, self::CACHE_TTL);
        }
    }
}
