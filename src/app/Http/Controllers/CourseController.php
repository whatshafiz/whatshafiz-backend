<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TeacherStudent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        if (Cache::has(Course::AVAILABLE_COURSES_CACHE_KEY)) {
            $availableCourses = Cache::get(Course::AVAILABLE_COURSES_CACHE_KEY);
        } else {
            $availableCourses = Course::available()
                ->get([
                    'id',
                    'type',
                    'name',
                    'start_at',
                    'can_be_applied',
                    'can_be_applied_until',
                    'proficiency_exam_start_time',
                ]);
            Cache::put(Course::AVAILABLE_COURSES_CACHE_KEY, $availableCourses);
        }

        return response()->json($availableCourses);
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
                'start_at' => 'nullable|date_format:Y-m-d\TH:i',
                'can_be_applied_until' => 'nullable|date_format:Y-m-d\TH:i',
                'proficiency_exam_start_time' => 'nullable|date_format:Y-m-d\TH:i',
            ]
        );

        if (isset($validatedCourseData['start_at'])) {
            $validatedCourseData['start_at'] = Carbon::parse($validatedCourseData['start_at'])->format('Y-m-d H:i:s');
        }

        if (isset($validatedCourseData['can_be_applied_until'])) {
            $validatedCourseData['can_be_applied_until'] = Carbon::parse($validatedCourseData['can_be_applied_until'])
                ->format('Y-m-d H:i:s');
        }

        if (isset($validatedCourseData['proficiency_exam_start_time'])) {
            $validatedCourseData['proficiency_exam_start_time'] = Carbon::parse(
                    $validatedCourseData['proficiency_exam_start_time']
                )
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

        $course->total_users_count = $course->users()->count();
        $course->whatsapp_groups_count = $course->whatsappGroups()->count();;
        $course->hafizkal_users_count = $course->users()->where('is_teacher', true)->count();
        $course->hafizol_users_count = $course->users()->where('is_teacher', false)->count();
        $course->matched_hafizkal_users_count = TeacherStudent::where('course_id', $course->id)
            ->groupBy('teacher_id')
            ->get('teacher_id')
            ->count();
        $course->matched_hafizol_users_count = TeacherStudent::where('course_id', $course->id)
            ->groupBy('student_id')
            ->get('student_id')
            ->count();
        $course->matched_users_count = $course->matched_hafizkal_users_count + $course->matched_hafizol_users_count;
        $course->unmatched_users_count = $course->total_users_count - $course->matched_users_count;

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
                'start_at' => 'nullable|date_format:Y-m-d\TH:i',
                'can_be_applied_until' => 'nullable|date_format:Y-m-d\TH:i',
                'proficiency_exam_start_time' => 'nullable|date_format:Y-m-d\TH:i',
            ]
        );

        if (isset($validatedCourseData['start_at'])) {
            $validatedCourseData['start_at'] = Carbon::parse($validatedCourseData['start_at'])->format('Y-m-d H:i:s');
        }

        if (isset($validatedCourseData['can_be_applied_until'])) {
            $validatedCourseData['can_be_applied_until'] = Carbon::parse($validatedCourseData['can_be_applied_until'])
                ->format('Y-m-d H:i:s');
        }

        if (isset($validatedCourseData['proficiency_exam_start_time'])) {
            $validatedCourseData['proficiency_exam_start_time'] = Carbon::parse(
                    $validatedCourseData['proficiency_exam_start_time']
                )
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
