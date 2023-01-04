<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CourseController extends Controller
{
    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Course::class);

        $requestData = $this->validate(
            $request,
            [
                'type' => 'nullable|string|in:whatshafiz,whatsenglish,whatsarapp',
                'name' => 'nullable|string|min:3|max:100',
                'is_active' => 'nullable|boolean',
                'can_be_applied' => 'nullable|boolean',
            ]
        );

        $courses = Course::latest()
            ->when(isset($requestData['type']), function ($query) use ($requestData) {
                return $query->where('type', $requestData['type']);
            })
            ->when(isset($requestData['name']), function ($query) use ($requestData) {
                return $query->where('name', 'LIKE', '%' . $requestData['name'] . '%');
            })
            ->when(isset($requestData['is_active']), function ($query) use ($requestData) {
                return $query->where('is_active', $requestData['is_active']);
            })
            ->when(isset($requestData['can_be_applied']), function ($query) use ($requestData) {
                return $requestData['can_be_applied'] ? $query->available() : $query->unavailable();
            })
            ->get();

        return response()->json(compact('courses'));
    }

    /**
     * @return JsonResponse
     */
    public function indexAvailableCourses(): JsonResponse
    {
        return response()->json(Course::available()->get(['id', 'type', 'name', 'can_be_applied_until', 'start_at']));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Course::class);

        $validatedCourseData = $this->validate(
            $request,
            [
                'type' => 'required|string|in:whatshafiz,whatsenglish,whatsarapp',
                'name' => 'required|string|min:3|max:100|unique:courses',
                'is_active' => 'required|boolean',
                'can_be_applied' => [
                    'required',
                    'boolean',
                    function ($attribute, $can_be_applied, $fail) use ($request) {
                        if ($can_be_applied &&
                            Course::where('can_be_applied', true)->where('type', $request->type)->exists()
                        ) {
                            $fail('Mevcutta zaten başvuruya açık dönem bulunuyor.');
                        }
                    },
                ],
                'can_be_applied_until' => 'nullable|date_format:Y-m-d H:i:s',
                'start_at' => 'nullable|date_format:Y-m-d H:i:s',
            ]
        );

        return response()->json(Course::create($validatedCourseData)->toArray(), Response::HTTP_CREATED);
    }

    /**
     * @param  Course  $course
     * @return JsonResponse
     */
    public function show(Course $course): JsonResponse
    {
        $this->authorize('view', Course::class);

        return response()->json($course->toArray());
    }

    /**
     * @param  Request  $request
     * @param  Course  $course
     * @return JsonResponse
     */
    public function update(Request $request, Course $course): JsonResponse
    {
        $this->authorize('update', Course::class);

        $validatedCourseData = $this->validate(
            $request,
            [
                'type' => 'required|string|in:whatshafiz,whatsenglish,whatsarapp',
                'name' => 'required|string|min:3|max:100|unique:courses',
                'is_active' => 'required|boolean',
                'can_be_applied' => [
                    'required',
                    'boolean',
                    function ($attribute, $can_be_applied, $fail) use ($request, $course) {
                        if ($can_be_applied &&
                            Course::where('can_be_applied', true)
                                ->where('id', '!=', $course->id)
                                ->where('type', $request->type)
                                ->exists()
                        ) {
                            $fail('Mevcutta zaten başvuruya açık dönem bulunuyor.');
                        }
                    },
                ],
                'can_be_applied_until' => 'nullable|date_format:Y-m-d H:i:s',
                'start_at' => 'nullable|date_format:Y-m-d H:i:s',
            ]
        );

        $course->update($validatedCourseData);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  Course  $course
     * @return JsonResponse
     */
    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', Course::class);

        //TODO: Course için başvuru yapılmışsa, aktif dönem varsa vb. durumlarda silme işlemi kontrole bağlı olacak.
        $course->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
