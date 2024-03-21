<?php

namespace Database\Seeders;

use App\Jobs\CourseTeacherStudentsMatcher;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Models\UserCourse;
use App\Models\WhatsappGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;

class ProductionLoadTestSeeder extends Seeder
{
    use WithFaker;

    protected array $whatsappGroupsJoinUrls = [
        'https://chat.whatsapp.com/JUDfhshcyZwIXnQYLOIPGm',
        'https://chat.whatsapp.com/EfGEDgTIAyoFvIXr6biluv',
        'https://chat.whatsapp.com/L8mzTRS16Kp0GhXS5j0K6n',
        'https://chat.whatsapp.com/F3QCxYEWtCmJj2A5v1E9l4',
        'https://chat.whatsapp.com/Dz0IEnSubJE66zrO5n986G',
        'https://chat.whatsapp.com/IMIcvLEs8Mg0VObhnYcO4i',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->setUpFaker();

        $this->generateWhatsenglishCourseAndUsers();

        $this->generateWhatshafizCourseAndUsers();

        $this->generateWhatshafizCourseAndUsers(true);
    }

    /**
     * @return void
     */
    public function generateWhatsenglishCourseAndUsers()
    {
        $course = Course::factory()->whatsenglish()->available()->create();
        WhatsappGroup::factory()
            ->count(50)
            ->create([
                'course_type_id' => CourseType::where('slug', 'whatsenglish')->value('id'),
                'course_id' => $course->id,
                'gender' => 'male',
                'is_active' => true,
                'join_url' => $this->faker->randomElement($this->whatsappGroupsJoinUrls),
            ]);
        WhatsappGroup::factory()
            ->count(50)
            ->create([
                'course_type_id' => CourseType::where('slug', 'whatsenglish')->value('id'),
                'course_id' => $course->id,
                'gender' => 'female',
                'is_active' => true,
                'join_url' => $this->faker->randomElement($this->whatsappGroupsJoinUrls),
            ]);

        $maleUsers = User::factory()->count(6500)->completed()->male()->create();
        $femaleUsers = User::factory()->count(3500)->completed()->female()->create();

        foreach ($maleUsers as $user) {
            UserCourse::factory()
                ->create([
                    'course_type_id' => CourseType::where('slug', 'whatsenglish')->value('id'),
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'is_teacher' => $this->faker->boolean(5),
                ]);
        }

        foreach ($femaleUsers as $user) {
            UserCourse::factory()
                ->create([
                    'course_type_id' => CourseType::where('slug', 'whatsenglish')->value('id'),
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'is_teacher' => $this->faker->boolean(10),
                ]);
        }
    }

    /**
     * @param  bool  $generateMatchings
     * @return void
     */
    public function generateWhatshafizCourseAndUsers($generateMatchings = false)
    {
        $course = Course::factory()->whatshafiz()->available()->create();
        WhatsappGroup::factory()
            ->count(50)
            ->create([
                'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                'course_id' => $course->id,
                'gender' => 'male',
                'is_active' => true,
                'join_url' => $this->faker->randomElement($this->whatsappGroupsJoinUrls),
            ]);
        WhatsappGroup::factory()
            ->count(50)
            ->create([
                'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                'course_id' => $course->id,
                'gender' => 'female',
                'is_active' => true,
                'join_url' => $this->faker->randomElement($this->whatsappGroupsJoinUrls),
            ]);

        $maleUsers = User::factory()->count(6500)->completed()->male()->create();
        $femaleUsers = User::factory()->count(3500)->completed()->female()->create();

        foreach ($maleUsers as $user) {
            UserCourse::factory()
                ->create([
                    'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'is_teacher' => $this->faker->boolean(5),
                ]);
        }

        foreach ($femaleUsers as $user) {
            UserCourse::factory()
                ->create([
                    'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'is_teacher' => $this->faker->boolean(10),
                ]);
        }

        if ($generateMatchings) {
            Config::set('queue.default', 'sync');
            resolve(CourseTeacherStudentsMatcher::class, ['course' => $course])->handle();

            TeacherStudent::where('course_id', $course->id)
                ->where('is_active', true)
                ->update(['proficiency_exam_passed' => true, 'proficiency_exam_failed_description' => null]);
        }
    }
}
