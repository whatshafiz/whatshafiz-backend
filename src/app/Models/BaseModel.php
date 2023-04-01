<?php

namespace App\Models;

use App\Traits\Tabulator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BaseModel extends Model
{
    use Tabulator;

    protected $guarded = [];
    protected $perPage = 10;
}
