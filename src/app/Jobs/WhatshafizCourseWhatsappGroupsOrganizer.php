<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\User;
use App\Models\WhatsappGroupUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WhatshafizCourseWhatsappGroupsOrganizer implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach (['male', 'female'] as $gender) {
            $whatsappGroupsQuery = $this->course->whatsappGroups()->where('gender', $gender);
            $whatsappGroupCount = $whatsappGroupsQuery->count();
            $teacherCount = $this->course->users()->where('is_teacher', true)->where('gender', $gender)->count();
            $studentCount = $this->course->users()->where('is_teacher', false)->where('gender', $gender)->count();
            $usersCount = $studentCount + $teacherCount;

            if ($whatsappGroupCount === 0 || $usersCount === 0) {
                continue;
            }

            $maxTeacherCountPerWhatsappGroup = (int)ceil($teacherCount / $whatsappGroupCount);
            $whatsappGroups = $this->getWhatsappGroupsForMatchings($gender, $maxTeacherCountPerWhatsappGroup);

            foreach ($whatsappGroups as $whatsappGroup) {
                $teacher = $this->findTeacherAndStudents($gender);

                if ($teacher) {
                    WhatsappGroupUser::create([
                        'whatsapp_group_id' => $whatsappGroup->id,
                        'course_id' => $whatsappGroup->course_id,
                        'user_id' => $teacher->id,
                        'role_type' => 'hafizkal',
                    ]);

                    foreach ($teacher->students as $student) {
                        WhatsappGroupUser::create([
                            'whatsapp_group_id' => $whatsappGroup->id,
                            'course_id' => $whatsappGroup->course_id,
                            'user_id' => $student->student_id,
                            'role_type' => 'hafizol',
                        ]);
                    }
                }
            }
        }

        $this->dispatchIf($this->existsUnmatchedUsers(), $this->course);
    }

    /**
     * @param  string  $gender
     * @param  int  $maxTeacherCountPerWhatsappGroup
     * @return Collection
     */
    public function getWhatsappGroupsForMatchings(string $gender, int $maxTeacherCountPerWhatsappGroup): Collection
    {
        $whatsappGroups = $this->course
            ->whatsappGroups()
            ->where('gender', $gender)
            ->withCount('teachers')
            ->having('teachers_count', '<', $maxTeacherCountPerWhatsappGroup)
            ->orderBy('teachers_count')
            ->get();

        if ($whatsappGroups->count() === 0) {
            $whatsappGroups = $this->course->whatsappGroups()->where('gender', $gender)->get();
        }

        return $whatsappGroups;
    }

    /**
     * @param  string  $gender
     * @return null|User
     */
    public function findTeacherAndStudents(string $gender): ?User
    {
        return $this->course
            ->users()
            ->where('is_teacher', true)
            ->where('gender', $gender)
            ->with('students', function ($query) {
                return $query->where('proficiency_exam_passed', true);
            })
            ->whereDoesntHave('whatsappGroups', function ($query) {
                return $query->where('whatsapp_groups.course_id', $this->course->id);
            })
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
            ->whereDoesntHave('whatsappGroups', function ($query) {
                return $query->where('whatsapp_groups.course_id', $this->course->id);
            })
            ->count();

        $unMatchedStudentsCount = $this->course
            ->users()
            ->where('is_teacher', false)
            ->whereHas('teachers', function ($query) {
                return $query->where('teacher_students.course_id', $this->course->id)
                    ->where('proficiency_exam_passed', true);
            })
            ->whereDoesntHave('whatsappGroups', function ($query) {
                return $query->where('whatsapp_groups.course_id', $this->course->id);
            })
            ->count();

        return ($unMatchedTeachersCount + $unMatchedStudentsCount) > 0;
    }
}
