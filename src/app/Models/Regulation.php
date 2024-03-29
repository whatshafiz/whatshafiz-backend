<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Regulation extends BaseModel
{
    use HasFactory;

    const BASE_CACHE_KEY = 'regulations:';

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            Cache::forget(self::BASE_CACHE_KEY . $model->slug);
        });
    }
}
