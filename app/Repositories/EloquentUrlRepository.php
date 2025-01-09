<?php

namespace App\Repositories;

use App\Models\ShortUrl;

class EloquentUrlRepository implements UrlRepositoryInterface
{
    public function encode(string $url): ?string
    {
        return ShortUrl::where('original_url_hash', md5($url))
            ->where('original_url', $url)
            ->first()
            ?->code;
    }

    public function decode(string $code): ?string
    {
        return ShortUrl::firstWhere('code', $code)
            ?->original_url;
    }

    public function save(string $url, string $code): void
    {
        ShortUrl::create([
            'original_url' => $url,
            'original_url_hash' => md5($url),
            'code' => $code,
        ]);
    }
}
