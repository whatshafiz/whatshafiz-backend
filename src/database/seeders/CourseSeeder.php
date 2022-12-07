<?php

namespace Database\Seeders;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Course::factory()->count(rand(1, 100))->create(['can_be_applied' => false]);
        Course::factory()
            ->whatshafiz()
            ->create(['can_be_applied' => true, 'can_be_applied_until' => Carbon::now()->addMonths(rand(1, 3))]);
        Course::factory()
            ->whatsarapp()
            ->create(['can_be_applied' => true, 'can_be_applied_until' => Carbon::now()->addMonths(rand(1, 3))]);
        Course::factory()
            ->whatsenglish()
            ->create(['can_be_applied' => true, 'can_be_applied_until' => Carbon::now()->addMonths(rand(1, 3))]);
    }
}
