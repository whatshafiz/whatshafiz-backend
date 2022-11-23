<?php

namespace Database\Seeders;

use App\Models\University;
use App\Models\UniversityDepartment;
use App\Models\UniversityFaculty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class UniversitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $universities = json_decode(File::get(database_path('data/universities.json')), true);
        University::insert($universities);

        $universityFaculties = json_decode(File::get(database_path('data/university_faculties.json')), true);
        UniversityFaculty::insert($universityFaculties);

        $allUniversityDepartments = json_decode(File::get(database_path('data/university_departments.json')), true);

        foreach (array_chunk($allUniversityDepartments, 1000) as $universityDepartments) {
            UniversityDepartment::insert($universityDepartments);
        }
    }
}
