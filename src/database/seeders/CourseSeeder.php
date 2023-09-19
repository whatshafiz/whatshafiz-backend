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
        Course::factory()->count(rand(1, 100))->unavailable()->create();
        Course::factory()->whatshafiz()->available()->create();
        Course::factory()->whatsarapp()->available()->create();
        Course::factory()->whatsenglish()->available()->create();
    }
}
