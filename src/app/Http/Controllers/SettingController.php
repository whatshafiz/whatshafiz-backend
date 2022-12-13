<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $cacheKey = 'settings';

        if (Cache::has($cacheKey)) {
            $settings = Cache::get($cacheKey);
        } else {
            $settings = Setting::get(['id', 'name', 'value']);
            Cache::put($cacheKey, $settings);
        }

        return response()->json(compact('settings'));
    }
}
