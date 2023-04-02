<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends BaseModel
{
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
    public $timestamps = false;

    /**
     * @return HasMany
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
