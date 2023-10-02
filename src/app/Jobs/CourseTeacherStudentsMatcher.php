<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseTeacherStudentsMatcher implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Course $course;

    /**
     * @param  Course  $course
     *
     * @return void
     */
    public function __construct(Course $course)
    {
        $this->course = $course;
    }

    /**
     * @return void
     */
    public function handle()
    {
        foreach (['male', 'female'] as $gender) {
            $teacherCount = $this->course->users()->where('is_teacher', true)->where('gender', $gender)->count();
            $studentCount = $this->course->users()->where('is_teacher', false)->where('gender', $gender)->count();

            if ($teacherCount === 0 || $studentCount === 0) {
                continue;
            }

            $maxStudentCountPerTeacher = (int)ceil($studentCount / $teacherCount);

            $teachers = $this->getTeachersForMatchings($gender, $maxStudentCountPerTeacher);

            foreach ($teachers as $teacher) {
                $student = $this->findStudentForTeacher($teacher);

                if ($student) {
                    $this->course
                        ->teacherStudentsMatchings()
                        ->create(['teacher_id' => $teacher->id, 'student_id' => $student->id]);
                }
            }
        }

        $this->dispatchIf($this->existsUnmatchedUsers(), $this->course);
    }

    /**
     * @param  string  $gender
     * @param  int  $maxStudentCountPerTeacher
     * @return Collection
     */
    public function getTeachersForMatchings(string $gender, int $maxStudentCountPerTeacher): Collection
    {
        $teachers = $this->course
            ->users()
            ->join('teacher_students', 'users.id', '=', 'teacher_students.teacher_id')
            ->where('user_course.course_id', $this->course->id)
            ->where('teacher_students.course_id', $this->course->id)
            ->where('is_teacher', true)
            ->where('users.gender', $gender)
            ->where(function($subQuery) {
                return $subQuery->where('teacher_students.proficiency_exam_passed', '=', '1')
                    ->orWhereNull('teacher_students.proficiency_exam_passed');
            })
            ->groupBy('teacher_students.teacher_id')
            ->addSelect(DB::raw('users.*, count(*) as student_count'))
            ->having('student_count', '<', $maxStudentCountPerTeacher)
            ->orderBy('student_count')
            ->get();

        if ($teachers->count() === 0) {
            $teachers = $this->course->users()->where('is_teacher', true)->where('gender', $gender)->get();
        }

        return $teachers;
    }

    /**
     * @param  User  $teacher
     * @return null|User
     */
    public function findStudentForTeacher(User $teacher): ?User
    {
        return $this->course
            ->users()
            ->where('is_teacher', false)
            ->where('gender', $teacher->gender)
            ->whereDoesntHave('teachers', function ($query) {
                return $query->where('course_id', $this->course->id);
            })
            ->where(function($query) use ($teacher) {
                $query->where(function($subQuery) use ($teacher) {
                    return $subQuery->where('country_id', $teacher->country_id)
                        ->where('city_id', $teacher->city_id)
                        ->where('education_level', $teacher->education_level)
                        ->where('university_id', $teacher->university_id);
                })
                ->orWhere(function($subQuery) use ($teacher) {
                    return $subQuery->where('country_id', $teacher->country_id)
                        ->where('city_id', $teacher->city_id)
                        ->where('education_level', $teacher->education_level);
                })
                ->orWhere(function($subQuery) use ($teacher) {
                    return $subQuery->where('country_id', $teacher->country_id)
                        ->where('education_level', $teacher->education_level)
                        ->where('university_id', $teacher->university_id);
                })
                ->orWhere(function($subQuery) use ($teacher) {
                    return $subQuery->where('country_id', $teacher->country_id)
                        ->where('education_level', $teacher->education_level);
                })
                ->orWhere(function($subQuery) use ($teacher) {
                    return $subQuery->where('country_id', $teacher->country_id);
                })
                ->orWhere(function($subQuery) use ($teacher) {
                    return $subQuery->where('education_level', $teacher->education_level);
                })
                ->orWhere(function($subQuery) use ($teacher) {
                    return $subQuery->where('university_id', $teacher->university_id);
                });
            })
            ->addSelect(DB::raw("users.*,
                CASE
                    WHEN country_id = '{$teacher->country_id}' and city_id = '{$teacher->city_id}' and education_level = '{$teacher->education_level}' and university_id = '{$teacher->university_id}' THEN '1'
                    WHEN country_id = '{$teacher->country_id}' and city_id = '{$teacher->city_id}' and education_level = '{$teacher->education_level}' THEN '2'
                    WHEN country_id = '{$teacher->country_id}' and education_level = '{$teacher->education_level}' and university_id = '{$teacher->university_id}' THEN '3'
                    WHEN country_id = '{$teacher->country_id}' and education_level = '{$teacher->education_level}' THEN '4'
                    WHEN university_id = '{$teacher->university_id}' THEN '5'
                    WHEN education_level = '{$teacher->education_level}' THEN '6'
                    WHEN country_id = '{$teacher->country_id}' THEN '7'
                    ELSE '99'
                END AS relation_level
            "))
            ->orderBy('relation_level')
            ->first();
    }

    /**
     * @return bool
     */
    public function existsUnmatchedUsers(): bool
    {
        $unMatchedTeachersCount = $this->course
            ->users()
            ->where('is_teacher', true)
            ->whereDoesntHave('students', function ($query) {
                return $query->where('course_id', $this->course->id);
            })
            ->count();

        $unMatchedStudentsCount = $this->course
            ->users()
            ->where('is_teacher', false)
            ->whereDoesntHave('teachers', function ($query) {
                return $query->where('course_id', $this->course->id);
            })
            ->count();

        return ($unMatchedTeachersCount + $unMatchedStudentsCount) > 0;
    }
}
