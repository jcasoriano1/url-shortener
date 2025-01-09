<?php

namespace Tests\Unit\Repositories;

use App\Models\ShortUrl;
use App\Repositories\EloquentUrlRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentUrlRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUrlRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentUrlRepository;
    }

    public function test_encode_finds_existing_url(): void
    {
        $url = 'https://example.com';
        $code = 'abc123';

        ShortUrl::create([
            'original_url' => $url,
            'code' => $code,
        ]);

        $result = $this->repository->encode($url);

        $this->assertEquals($code, $result);
    }

    public function test_encode_returns_null_for_nonexistent_url(): void
    {
        $result = $this->repository->encode('https://example.com');

        $this->assertNull($result);
    }

    public function test_decode_finds_existing_code(): void
    {
        $url = 'https://example.com';
        $code = 'abc123';

        ShortUrl::create([
            'original_url' => $url,
            'code' => $code,
        ]);

        $result = $this->repository->decode($code);

        $this->assertEquals($url, $result);
    }

    public function test_decode_returns_null_for_nonexistent_code(): void
    {
        $result = $this->repository->decode('nonexistent');

        $this->assertNull($result);
    }
}
