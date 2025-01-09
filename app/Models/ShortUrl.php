<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortUrl extends Model
{
    protected $fillable = ['original_url', 'code'];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->original_url_hash = md5($model->original_url);
        });
    }
}
