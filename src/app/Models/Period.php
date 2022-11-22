<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Period extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'can_be_applied' => 'boolean',
        'is_active' => 'boolean',
    ];
}
