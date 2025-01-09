<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UrlDecodeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'short_url' => ['required', 'url', 'max:255'],
        ];
    }
}
