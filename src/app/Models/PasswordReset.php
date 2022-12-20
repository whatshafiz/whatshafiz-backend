<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PasswordReset extends BaseModel
{
    const TOKEN_LIFETIME_IN_MINUTE = 3;

    protected $primaryKey = 'phone_number';

    public $incrementing = false;
    public $timestamps = false;

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subMinutes(self::TOKEN_LIFETIME_IN_MINUTE));
    }
}
