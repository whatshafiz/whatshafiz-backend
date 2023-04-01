<?php

namespace App\Models;

use App\Traits\Tabulator;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use Tabulator;

    protected $guarded = [];
    protected $perPage = 10;
}
