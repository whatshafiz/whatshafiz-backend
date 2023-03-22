<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
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
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request): JsonResponse
    {
        if(!Auth::user()->hasRole('Admin')) {
            return response()->json(['status' => 'failed'], Response::HTTP_FORBIDDEN);
        }

        $cacheKey = 'settings';
        Cache::forget($cacheKey);

        $validatedData = $this->validate(
            $request,
            [
                'settings' => 'required|array',
                'settings.*.id' => 'required|integer',
                'settings.*.value' => 'required|string',
            ]
        );

        foreach ($validatedData['settings'] as $setting) {
            Setting::where('id', $setting['id'])->update(['value' => $setting['value']]);
        }

        $settings = Setting::get(['id', 'name', 'value']);
        Cache::put($cacheKey, $settings);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
