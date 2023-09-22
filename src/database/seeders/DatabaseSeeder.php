<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolesPermissionsSeeder::class);
        $this->call(RegulationSeeder::class);
        $this->call(CountrySeeder::class);
        $this->call(CitySeeder::class);
        $this->call(UniversitySeeder::class);
        $this->call(SettingSeeder::class);

        if (!app()->isProduction()) {
            $this->call(UserSeeder::class);
            $this->call(CourseSeeder::class);
            $this->call(WhatsappGroupSeeder::class);
            $this->call(ComplaintSeeder::class);
            $this->call(CommentSeeder::class);
            $this->call(QuranQuestionSeeder::class);
            $this->call(AnswerAttemptSeeder::class);
            $this->call(WhatsappMessengerNumberSeeder::class);
            $this->call(TeacherStudentSeeder::class);
        }
    }
}
