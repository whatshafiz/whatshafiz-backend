<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UniversityFaculty extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * @return HasMany
     */
    public function departments(): HasMany
    {
        return $this->hasMany(UniversityDepartment::class);
    }
}
