<?php

namespace Tests;

use Illuminate\Database\Query\Expression;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;

class BaseFeatureTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    const BASE_URI = '/api/v1';

    /**
     * @param  array  $json
     * @return Expression
     */
    public function castToJson(array $json): Expression
    {
        return DB::raw("CAST('" . addslashes(json_encode($json)) . "' AS JSON)");
    }
}
