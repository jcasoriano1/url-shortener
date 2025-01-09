<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UrlEncodeRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UrlEncodeRequestTest extends TestCase
{
    private function validate(array $data): bool
    {
        return Validator::make($data, (new UrlEncodeRequest)->rules())->passes();
    }

    public function test_valid_url_passes_validation(): void
    {
        $this->assertTrue($this->validate([
            'url' => 'https://example.com/path',
        ]));
    }

    public function test_url_is_required(): void
    {
        $this->assertFalse($this->validate([
            'url' => '',
        ]));
    }

    public function test_url_must_be_valid_format(): void
    {
        $this->assertFalse($this->validate([
            'url' => 'not-a-url',
        ]));
    }

    public function test_url_must_not_exceed_max_length(): void
    {
        $this->assertFalse($this->validate([
            'url' => 'https://example.com/'.str_repeat('a', 2048),
        ]));
    }

    public function test_url_accepts_various_protocols(): void
    {
        $this->assertTrue($this->validate([
            'url' => 'http://example.com',
        ]));

        $this->assertTrue($this->validate([
            'url' => 'https://example.com',
        ]));

        $this->assertTrue($this->validate([
            'url' => 'ftp://example.com',
        ]));
    }
}
