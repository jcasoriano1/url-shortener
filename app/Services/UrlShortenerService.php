<?php

namespace App\Services;

use App\Exceptions\ShortUrlNotFound;
use App\Repositories\UrlRepositoryPipeline;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class UrlShortenerService
{
    private const CODE_LENGTH = 7; // 62 ^ 7 combinations = 3,521,614,606,208

    private const MAX_ATTEMPTS = 10;

    public function __construct(
        private UrlRepositoryPipeline $repository,
    ) {}

    public function encode(string $url): string
    {
        // check for existing code
        if ($code = $this->repository->encode($url)) {
            return $this->generateFullShortUrl($code);
        }

        // Generate new short code with collision handling
        $attempts = 0;
        do {
            $code = $this->generateUniqueCode();
            try {
                // cache the result
                $this->repository->save($url, $code);

                return $this->generateFullShortUrl($code);
            } catch (QueryException $e) {
                if (++$attempts >= self::MAX_ATTEMPTS) {
                    throw new Exception('Failed to generate unique code');
                }
            }
        } while (true);
    }

    public function decode(string $shortUrl): string
    {
        // extract code from short url
        $code = $this->extractCode($shortUrl);

        // check for existence
        if ($url = $this->repository->decode($code)) {
            return $url;
        }

        throw new ShortUrlNotFound;
    }

    private function generateUniqueCode(): string
    {
        return Str::random(self::CODE_LENGTH);
    }

    private function generateFullShortUrl(string $code): string
    {
        return config('app.url').'/s/'.$code;
    }

    private function extractCode(string $shortUrl): string
    {
        return Str::afterLast($shortUrl, '/');
    }
}
