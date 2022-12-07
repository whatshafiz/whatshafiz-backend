<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniversityFaculty extends BaseModel
{
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    use HasFactory;

    /**
     * @return HasMany
     */
    public function departments(): HasMany
    {
        return $this->hasMany(UniversityDepartment::class);
    }
}
