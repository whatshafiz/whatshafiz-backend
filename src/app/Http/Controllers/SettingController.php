<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * @param  Request  $request
     * @param  Setting  $setting
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, Setting $setting): JsonResponse
    {
        if (!Auth::user()->hasRole('Admin')) {
            return response()->json([], Response::HTTP_FORBIDDEN);
        }

        $request->validate(['value' => 'required|string']);

        $setting->update(['value' => $request->value]);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
