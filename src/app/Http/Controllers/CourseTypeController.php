<?php

namespace App\Http\Controllers;

use App\Models\CourseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class CourseTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function indexPaginate(Request $request): JsonResponse
    {
        $searchKey = $this->getTabulatorSearchKey($request);

        $questions = CourseType::withCount('courses', 'whatsappGroups', 'userCourses', 'comments')
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where('id', $searchKey)
                    ->orWhere('name', 'LIKE', '%' . $searchKey . '%')
                    ->orWhere('slug', 'LIKE', '%' . $searchKey . '%');
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends($this->filters);

        return response()->json($questions->toArray());
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $courseTypes = CourseType::get();

        return response()->json(compact('courseTypes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validatedCourseTypeData = $this->validate($request, [
            'parent_id' => 'nullable|integer|min:1|exists:course_types,id',
            'name' => 'required|string|max:255|unique:course_types',
            'slug' => 'required|string|max:255|unique:course_types',
            'is_active' => 'required|boolean',
            'has_admission_exam' => 'required|boolean',
            'min_age' => 'nullable|integer|min:1|max:120',
            'genders' => 'required|array|min:1',
            'genders.*' => 'required|string|in:male,female',
            'education_levels' => 'nullable|array',
            'education_levels.*' => 'required|string|exists:education_levels,name',
        ]);

        $courseType = CourseType::create($validatedCourseTypeData);

        return response()->json(compact('courseType'), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param CourseType $courseType
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(CourseType $courseType): JsonResponse
    {
        return response()->json(compact('courseType'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param CourseType $courseType
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, CourseType $courseType): JsonResponse
    {
        $validatedCourseTypeData = $this->validate($request, [
            'parent_id' => 'nullable|integer|min:1|exists:course_types,id',
            'name' => 'required|string|max:255|unique:course_types,name,' . $courseType->id,
            'slug' => 'required|string|max:255|unique:course_types,slug,' . $courseType->id,
            'is_active' => 'required|boolean',
            'has_admission_exam' => 'required|boolean',
            'min_age' => 'nullable|integer|min:1|max:120',
            'genders' => 'required|array|min:1',
            'genders.*' => 'required|string|in:male,female',
            'education_levels' => 'nullable|array',
            'education_levels.*' => 'required|string|exists:education_levels,name',
        ]);

        $courseType->update($validatedCourseTypeData);

        return response()->json(compact('courseType'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param CourseType $courseType
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(CourseType $courseType): JsonResponse
    {
        $courseType->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
