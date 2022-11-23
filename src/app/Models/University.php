<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class University extends BaseModel
{
    use HasFactory;

    /**
     * @return HasMany
     */
    public function faculties(): HasMany
    {
        return $this->hasMany(UniversityFaculty::class);
    }
}
