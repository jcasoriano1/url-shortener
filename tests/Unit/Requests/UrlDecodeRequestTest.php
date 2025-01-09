<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UrlDecodeRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UrlDecodeRequestTest extends TestCase
{
    private function validate(array $data): bool
    {
        return Validator::make($data, (new UrlDecodeRequest)->rules())->passes();
    }

    public function test_valid_short_url_passes_validation(): void
    {
        $this->assertTrue($this->validate([
            'short_url' => 'https://example.com/s/abc123',
        ]));
    }

    public function test_short_url_is_required(): void
    {
        $this->assertFalse($this->validate([
            'short_url' => '',
        ]));
    }

    public function test_short_url_must_be_valid_format(): void
    {
        $this->assertFalse($this->validate([
            'short_url' => 'not-a-url',
        ]));
    }

    public function test_short_url_must_not_exceed_max_length(): void
    {
        $this->assertFalse($this->validate([
            'short_url' => 'https://example.com/'.str_repeat('a', 255),
        ]));
    }

    public function test_short_url_accepts_various_domains(): void
    {
        $this->assertTrue($this->validate([
            'short_url' => 'https://short.com/s/abc123',
        ]));

        $this->assertTrue($this->validate([
            'short_url' => 'https://localhost/s/abc123',
        ]));

        $this->assertTrue($this->validate([
            'short_url' => 'https://127.0.0.1/s/abc123',
        ]));
    }
}
