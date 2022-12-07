<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UniversityDepartment extends BaseModel
{
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    use HasFactory;
}
