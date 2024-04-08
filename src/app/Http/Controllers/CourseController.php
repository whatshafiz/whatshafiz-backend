<?php

namespace App\Http\Controllers;

use App\Jobs\CourseTeacherStudentsMatcher;
use App\Jobs\CourseWhatsappGroupsOrganizer;
use App\Jobs\WhatshafizCourseWhatsappGroupsOrganizer;
use App\Models\Course;
use App\Models\TeacherStudent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            ->with('courseType:id,name')
            ->when(isset($filters['user_id']), function ($query) use ($filters) {
                return $query->whereHas('users', function ($subQuery) use ($filters) {
                    return $subQuery->where('users.id', $filters['user_id']);
                });
            })
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where(function ($subQuery) use ($searchKey) {
                    return $subQuery->where('id', $searchKey)
                        ->orWhere('name', 'LIKE', '%' . $searchKey . '%');
                });
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends(array_merge($this->filters, $filters));

        return response()->json($courses->toArray());
    }

    /**
     * @param  Request  $request
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
                ->with('courseType:id,name')
                ->get([
                    'id',
                    'course_type_id',
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
                'course_type_id' => 'required|integer|min:1|exists:course_types,id',
                'name' => 'required|string|min:3|max:100|unique:courses',
                'whatsapp_channel_join_url' => 'nullable|url',
                'is_active' => 'required|boolean',
                'can_be_applied' => [
                    'required',
                    'boolean',
                    function ($attribute, $can_be_applied, $fail) use ($request) {
                        if ($can_be_applied &&
                            Course::where('can_be_applied', true)
                                ->where('course_type_id', $request->course_type_id)
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
        $course->whatsapp_groups_count = $course->whatsappGroups()->count();
        $course->whatsapp_groups_users_count = $course->whatsappGroupUsers()->count();
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
        $course->load('courseType:id,name');

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
                'course_type_id' => 'required|integer|min:1|exists:course_types,id',
                'name' => 'required|string|min:3|max:100|unique:courses,name,' . $course->id,
                'whatsapp_channel_join_url' => 'nullable|url',
                'is_active' => 'required|boolean',
                'can_be_applied' => [
                    'required',
                    'boolean',
                    function ($attribute, $can_be_applied, $fail) use ($request, $course) {
                        if ($can_be_applied &&
                            Course::where('can_be_applied', true)
                                ->where('id', '!=', $course->id)
                                ->where('course_type_id', $request->course_type_id)
                                ->where('can_be_applied_until', '>', now())
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

    /**
     * @param  Course  $course
     * @return JsonResponse
     */
    public function startTeacherStudentsMatchings(Course $course): JsonResponse
    {
        $this->authorize('update', [Course::class, $course]);

        CourseTeacherStudentsMatcher::dispatch($course);

        $course->students_matchings_started_at = now();
        $course->save();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  Request  $request
     * @param  Course  $course
     * @return JsonResponse
     */
    public function getTeacherStudentsMatchings(Request $request, Course $course): JsonResponse
    {
        $this->authorize('view', [Course::class, $course]);

        $filters = $this->validate($request, ['teacher_id' => 'nullable|integer|exists:users,id']);
        $searchKey = $this->getTabulatorSearchKey($request);

        $teacherStudentsMatchings = $course->teacherStudentsMatchings()
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where(function ($subQuery) use ($searchKey) {
                    return $subQuery->where('id', $searchKey)
                        ->orWhereHas('teacher', function ($subQuery) use ($searchKey) {
                            return $subQuery->where('id', $searchKey)
                                ->orWhere(DB::raw('CONCAT(name, " ", surname)'), 'like', "%{$searchKey}%")
                                ->orWhere('phone_number', 'like', "%{$searchKey}%");
                        });
                });
            })
            ->when(!empty($filters['teacher_id']), function ($query) use ($filters) {
                return $query->where('teacher_id', $filters['teacher_id'])
                    ->with('student:id,name,surname,email,gender,phone_number');
            })
            ->when(empty($filters['teacher_id']), function ($query) {
                return $query->groupBy('teacher_id')
                    ->select(DB::raw('*, COUNT(student_id) as students_count'))
                    ->addSelect(
                        DB::raw('SUM(CASE WHEN proficiency_exam_passed = 1 THEN 1 ELSE 0 END) AS passed_students_count')
                    )
                    ->addSelect(
                        DB::raw('SUM(CASE WHEN proficiency_exam_passed = 0 THEN 1 ELSE 0 END) AS failed_students_count')
                    )
                    ->addSelect(
                        DB::raw('SUM(CASE WHEN proficiency_exam_passed IS NULL THEN 1 ELSE 0 END) AS awaiting_students_count')
                    );
            })
            ->with('teacher:id,name,surname,email,gender,phone_number')
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends(array_merge($this->filters, $filters));

        return response()->json($teacherStudentsMatchings->toArray());
    }

    /**
     * @param  Course  $course
     * @return JsonResponse
     */
    public function organizeWhatsappGroups(Course $course): JsonResponse
    {
        $this->authorize('update', [Course::class, $course]);

        if ($course->courseType->slug === 'whatshafiz') {
            WhatshafizCourseWhatsappGroupsOrganizer::dispatch($course);
        } else {
            CourseWhatsappGroupsOrganizer::dispatch($course);
        }

        $course->students_matchings_started_at = now();
        $course->save();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
