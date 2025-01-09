<?php

namespace App\Http\Controllers;

use App\Http\Requests\UrlDecodeRequest;
use App\Http\Requests\UrlEncodeRequest;
use App\Services\UrlShortenerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class UrlShortenerController extends Controller
{
    public function __construct(
        private UrlShortenerService $service,
    ) {}

    public function encode(UrlEncodeRequest $request): JsonResponse
    {
        $shortUrl = $this->service->encode($request->url);

        return response()->json(['short_url' => $shortUrl], 201);
    }

    public function decode(UrlDecodeRequest $request): JsonResponse
    {
        $originalUrl = $this->service->decode($request->short_url);

        return response()->json(['original_url' => $originalUrl]);
    }

    public function redirect(string $code): RedirectResponse
    {
        $originalUrl = $this->service->decode($code);

        return redirect($originalUrl);
    }
}
