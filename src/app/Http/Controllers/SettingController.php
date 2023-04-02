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
    public $cacheKey = 'settings';

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        if (Cache::has($this->cacheKey)) {
            $settings = Cache::get($this->cacheKey);
        } else {
            $settings = Setting::select(['id', 'name', 'value'])->paginate()->toArray();
            Cache::put($this->cacheKey, $settings);
        }

        return response()->json($settings);
    }

    /**
     * Display the specified resource.
     *
     * @param Setting $setting
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(Setting $setting): JsonResponse
    {
        return response()->json(compact('setting'));
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

        Cache::forget($this->cacheKey);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
