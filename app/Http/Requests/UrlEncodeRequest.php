<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UrlEncodeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
        ];
    }
}
