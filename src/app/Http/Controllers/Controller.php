<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public $filters = [];

    /**
     * @param  Request  $request
     * @return string
     */
    public function getTabulatorSearchKey(Request $request)
    {
        $this->filters = $this->validate($request, [
            'size' => 'nullable|integer',
            'filter.0.value' => 'nullable|string|max:255',
        ]);

        return Arr::get($this->filters, 'filter.0.value');
    }
}
