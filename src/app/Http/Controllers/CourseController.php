<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CourseController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Course::class);

        $courses = Course::latest()->get();

        return response()->json(compact('courses'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function indexPaginate(Request $request): JsonResponse
    {
        $this->authorize('viewAny', [Course::class, $request->user_id]);

        $filters = $this->validate($request, ['user_id' => 'nullable|integer|exists:users,id']);
        $searchKey = $this->getTabulatorSearchKey($request);

        $courses = Course::withCount('users')
            ->when(isset($filters['user_id']), function ($query) use ($filters) {
                return $query->whereHas('users', function ($subQuery) use ($filters) {
                    return $subQuery->where('users.id', $filters['user_id']);
                });
            })
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where(function ($subQuery) use ($searchKey) {
                    return $subQuery->where('id', $searchKey)
                        ->orWhere('type', 'LIKE', '%' . $searchKey . '%')
                        ->orWhere('name', 'LIKE', '%' . $searchKey . '%');
                });
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends(array_merge($this->filters, $filters));

        return response()->json($courses->toArray());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function myCourses(Request $request): JsonResponse
    {
        $request->merge(['user_id' => Auth::id()]);

        return $this->indexPaginate($request);
    }

    /**
     * @return JsonResponse
     */
    public function indexAvailableCourses(): JsonResponse
    {
        return response()->json(
            Course::available()->get(['id', 'type', 'name', 'can_be_applied', 'can_be_applied_until', 'start_at'])
        );
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
                'can_be_applied_until' => 'nullable|date_format:d-m-Y H:i',
                'start_at' => 'nullable|date_format:d-m-Y H:i',
            ]
        );

        if (isset($validatedCourseData['start_at'])) {
            $validatedCourseData['start_at'] = Carbon::parse($validatedCourseData['start_at'])->format('Y-m-d H:i:s');
        }

        if (isset($validatedCourseData['can_be_applied_until'])) {
            $validatedCourseData['can_be_applied_until'] = Carbon::parse($validatedCourseData['can_be_applied_until'])
                ->format('Y-m-d H:i:s');
        }

        $course = Course::create($validatedCourseData);

        return response()->json($course->toArray(), Response::HTTP_CREATED);
    }

    /**
     * @param  Course  $course
     * @return JsonResponse
     */
    public function show(Course $course): JsonResponse
    {
        $this->authorize('view', [Course::class, $course]);

        return response()->json(compact('course'));
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
                'name' => 'required|string|min:3|max:100|unique:courses,name,' . $course->id,
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
                'can_be_applied_until' => 'nullable|date_format:d-m-Y H:i',
                'start_at' => 'nullable|date_format:d-m-Y H:i',
            ]
        );

        if (isset($validatedCourseData['start_at'])) {
            $validatedCourseData['start_at'] = Carbon::parse($validatedCourseData['start_at'])->format('Y-m-d H:i:s');
        }

        if (isset($validatedCourseData['can_be_applied_until'])) {
            $validatedCourseData['can_be_applied_until'] = Carbon::parse($validatedCourseData['can_be_applied_until'])
                ->format('Y-m-d H:i:s');
        }

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
