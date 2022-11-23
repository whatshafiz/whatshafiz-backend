<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniversityFaculty extends BaseModel
{
    use HasFactory;

    /**
     * @return HasMany
     */
    public function departments(): HasMany
    {
        return $this->hasMany(UniversityDepartment::class);
    }
}
