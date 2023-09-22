<?php

namespace Database\Seeders;

use App\Models\TeacherStudent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeacherStudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TeacherStudent::factory()->count(rand(20, 100))->create();
    }
}
