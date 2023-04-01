<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Tabulator
{
    /**
     * @param  Builder  $query
     * @param  Request  $request
     * @return Builder
     */
    public function scopeOrderByTabulator(Builder $query, Request $request): Builder
    {
        return $query->orderBy($request->sort[0]['field'] ?? 'id', $request->sort[0]['dir'] ?? 'desc');
    }
}
