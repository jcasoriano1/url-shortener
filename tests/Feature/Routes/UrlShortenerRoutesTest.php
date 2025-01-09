<?php

namespace Tests\Feature\Routes;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UrlShortenerRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('octane')->clear();
        Cache::store('redis')->clear();
    }

    public function test_encode_endpoint_creates_short_url(): void
    {
        $this->postJson('/api/encode', [
            'url' => 'https://example.com/very/long/url',
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['short_url']);
    }

    public function test_decode_endpoint_returns_original_url(): void
    {
        // First create a short URL
        $response = $this->postJson('/api/encode', [
            'url' => 'https://example.com/very/long/url',
        ]);

        $shortUrl = $response->json('short_url');

        // Then decode it
        $response = $this->postJson('/api/decode', [
            'short_url' => $shortUrl,
        ]);

        $response->assertOk()
            ->assertJson(['original_url' => 'https://example.com/very/long/url']);
    }

    public function test_encode_validates_url(): void
    {
        $response = $this->postJson('/api/encode', [
            'url' => 'not-a-url',
        ]);

        $response->assertStatus(422);
    }

    public function test_decode_validates_url(): void
    {
        $response = $this->postJson('/api/decode', [
            'short_url' => 'not-a-url',
        ]);

        $response->assertStatus(422);
    }

    public function test_redirect_endpoint_redirects_to_original_url(): void
    {
        // First create a short URL
        $response = $this->postJson('/api/encode', [
            'url' => 'https://example.com/very/long/url',
        ]);

        $shortUrl = $response->json('short_url');

        // Extract the code from the short URL
        $code = basename($shortUrl);

        // Test the redirect endpoint
        $response = $this->get("/s/$code");

        $response->assertStatus(302)
            ->assertRedirect('https://example.com/very/long/url');
    }

    public function test_redirect_endpoint_returns_404_for_invalid_code(): void
    {
        $response = $this->get('/invalid-code');

        $response->assertStatus(404);
    }
}
