<?php

namespace App\Repositories;

interface UrlRepositoryInterface
{
    public function encode(string $url): ?string;

    public function decode(string $code): ?string;

    public function save(string $url, string $code): void;
}
