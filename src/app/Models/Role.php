<?php

namespace App\Models;

use App\Traits\Tabulator;
use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    use Tabulator;

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
    ];

    public $appends = ['users_count'];

    /**
     * @return int
     */
    public function getUsersCountAttribute()
    {
        return User::role($this->name)->count();
    }
}
