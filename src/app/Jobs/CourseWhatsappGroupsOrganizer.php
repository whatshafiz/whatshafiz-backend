<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\User;
use App\Models\UserCourse;
use App\Models\WhatsappGroup;
use App\Models\WhatsappGroupUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CourseWhatsappGroupsOrganizer implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected Course $course;
    protected int $level;

    /**
     * @param  Course  $course
     * @param  int  $level
     *
     * @return void
     */
    public function __construct(Course $course, int $level = 1)
    {
        $this->course = $course;
        $this->level = $level;
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
            $usersQuery = $this->course->users()->where('gender', $gender);
            $usersCount = $usersQuery->count();

            if ($whatsappGroupCount === 0 || $usersCount === 0) {
                continue;
            }

            $avgUserCountPerGroup = ceil($usersCount / $whatsappGroupCount);
            $groups = $this->getSimilarUsersForGroups($gender, $this->course->id, $this->level);

            $groupMembers = [];

            foreach ($groups as $group) {
                $groupMembers[$group->group_number][] = $group->user_id;
            }

            foreach ($groupMembers as $groupNumber => $userIds) {
                $whatsappGroup = WhatsappGroup::where('course_id', $this->course->id)
                    ->where('gender', $gender)
                    ->withCount('users')
                    ->orderBy('users_count')
                    ->first();

                $users = [];

                foreach ($userIds as $userId) {
                    $users[] = [
                        'whatsapp_group_id' => $whatsappGroup->id,
                        'course_id' => $this->course->id,
                        'user_id' => $userId,
                    ];
                }

                WhatsappGroupUser::insert($users);
                UserCourse::where('course_id', $this->course->id)
                    ->whereIn('user_id', $userIds)
                    ->update(['whatsapp_group_id' => $whatsappGroup->id]);
            }
        }

        $this->dispatchIf($this->existsUngroupedUsers(), $this->course, ++$this->level);
    }

    /**
     * @param  string  $gender
     * @param  int  $courseId
     * @param  int  $level
     * @return array
     */
    public function getSimilarUsersForGroups(string $gender, int $courseId, int $level): array
    {
        $levelColumns = [
            1 => 'country_id, city_id, education_level, university_id',
            2 => 'country_id, city_id, education_level',
            3 => 'country_id, city_id, education_level, university_id',
            4 => 'country_id, education_level, university_id',
            5 => 'country_id, education_level',
            6 => 'country_id, city_id',
            7 => 'education_level',
            8 => 'country_id',
            9 => 'university_id',
            10 => 'is_banned',
        ];

        return DB::select(
            '
                SELECT subquery2.*
                FROM (
                  SELECT
                    subquery.*,
                    COUNT(*) OVER (
                      PARTITION BY subquery.group_number
                    ) AS group_member_count
                  FROM
                    (
                      SELECT
                        users.id as user_id,
                        DENSE_RANK() OVER (
                          ORDER BY
                            ' . $levelColumns[$level] . '
                        ) AS group_number
                      FROM
                        users
                        INNER JOIN user_course ON user_course.user_id = users.id
                      WHERE
                        user_course.course_id = ?
                        AND users.gender = ?
                        AND users.is_banned = 0
                        AND user_course.whatsapp_group_id IS NULL
                        AND user_course.deleted_at IS NULL
                        AND users.country_id IS NOT NULL
                        AND users.city_id IS NOT NULL
                        AND users.education_level IS NOT NULL
                    ) as subquery
                  ) as subquery2
                WHERE subquery2.group_member_count > ?
                ORDER BY subquery2.group_number ASC;
            ',
            [$courseId, $gender, ($level < count($levelColumns) ? 1 : 0)]
        );
    }

    /**
     * @return bool
     */
    public function existsUngroupedUsers(): bool
    {
        return UserCourse::where('course_id', $this->course->id)->whereNull('whatsapp_group_id')->exists();
    }
}
