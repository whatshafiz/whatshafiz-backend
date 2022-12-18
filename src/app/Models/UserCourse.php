<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCourse extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'user_course';
}
