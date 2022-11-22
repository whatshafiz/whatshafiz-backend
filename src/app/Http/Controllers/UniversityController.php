<?php

namespace App\Http\Controllers;

use App\Models\University;
use App\Models\UniversityFaculty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class UniversityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $cacheKey = 'universities';

        if (Cache::has($cacheKey)) {
            $universities = Cache::get($cacheKey);
        } else {
            $universities = University::orderBy('name')->get(['id', 'name']);
            Cache::put($cacheKey, $universities);
        }

        return response()->json(compact('universities'));
    }

    /**
     * Display a listing of the resource.
     *
     * @param  University  $university
     * @return JsonResponse
     */
    public function faculties(University $university): JsonResponse
    {
        $cacheKey = "universities:{$university->id}:faculties";

        if (Cache::has($cacheKey)) {
            $faculties = Cache::get($cacheKey);
        } else {
            $faculties = $university->faculties()->get(['id', 'name']);
            Cache::put($cacheKey, $faculties);
        }

        return response()->json(compact('faculties'));
    }

    /**
     * Display a listing of the resource.
     *
     * @param  University  $university
     * @param  UniversityFaculty  $faculty
     * @return JsonResponse
     */
    public function departments(University $university, UniversityFaculty $faculty): JsonResponse
    {
        $cacheKey = "universities:{$university->id}:faculties:{$faculty->id}:departments";

        if (Cache::has($cacheKey)) {
            $departments = Cache::get($cacheKey);
        } else {
            $departments = $faculty->departments()->get(['id', 'name']);
            Cache::put($cacheKey, $departments);
        }

        return response()->json(compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|min:5|max:250|unique:universities']);

        $university = University::create(['name' => $request->name]);

        Cache::forget("universities");

        return response()->json(compact('university'), Response::HTTP_CREATED);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  University  $university
     * @param  Request  $request
     * @return JsonResponse
     */
    public function storeFaculty(University $university, Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:5|max:250' .
                '|unique:university_faculties,name,NULL,NULL,university_id,' . $university->id,
        ]);

        $faculty = $university->faculties()->create(['name' => $request->name]);

        Cache::forget("universities:{$university->id}:faculties");

        return response()->json(compact('faculty'), Response::HTTP_CREATED);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  University  $university
     * @param  UniversityFaculty  $faculty
     * @param  Request  $request
     * @return JsonResponse
     */
    public function storeDepartment(University $university, UniversityFaculty $faculty, Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:5|max:250' .
                '|unique:university_departments,name,NULL,NULL,university_id,' . $university->id,
        ]);

        $department = $faculty->departments()
            ->create(['university_id' => $faculty->university_id, 'name' => $request->name]);

        Cache::forget("universities:{$university->id}:faculties:{$faculty->id}:departments");

        return response()->json(compact('department'), Response::HTTP_CREATED);
    }
}
