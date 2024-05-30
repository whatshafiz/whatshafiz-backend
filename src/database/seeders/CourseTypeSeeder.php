<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $educationLevels = json_encode(EducationLevel::pluck('name')->toArray());
        $genders = json_encode(['male', 'female']);

        DB::table('course_types')
            ->insert([
                [
                    'id' => 1,
                    'parent_id' => null,
                    'name' => 'WhatsHafız',
                    'slug' => 'whatshafiz',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => true,
                ],
                [
                    'id' => 2,
                    'parent_id' => 1,
                    'name' => 'HafızOl',
                    'slug' => 'hafizol',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => false,
                ],
                [
                    'id' => 3,
                    'parent_id' => 1,
                    'name' => 'HafızKal',
                    'slug' => 'hafizkal',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => false,
                ]
            ]);

        DB::table('course_types')
            ->insert([
                [
                    'id' => 4,
                    'parent_id' => null,
                    'name' => 'WhatsEnglish',
                    'slug' => 'whatsenglish',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => true,
                ],
                [
                    'id' => 5,
                    'parent_id' => 4,
                    'name' => 'WhatsEnglish 1. Kur',
                    'slug' => 'whatsenglish-1-kur',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => false,
                ],
                [
                    'id' => 6,
                    'parent_id' => 4,
                    'name' => 'WhatsEnglish 2. Kur',
                    'slug' => 'whatsenglish-2-kur',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => true,
                ],
                [
                    'id' => 7,
                    'parent_id' => 4,
                    'name' => 'WhatsEnglish YDS',
                    'slug' => 'whatsenglish-yds',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => true,
                ],
                [
                    'id' => 8,
                    'parent_id' => 4,
                    'name' => 'WhatsEnglish Ortaokul/Lise',
                    'slug' => 'whatsenglish-ortaokul-lise',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => false,
                ],
            ]);

        DB::table('course_types')
            ->insert([
                [
                    'id' => 9,
                    'parent_id' => null,
                    'name' => 'WhatsArapp',
                    'slug' => 'whatsarapp',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => true,
                ],
                [
                    'id' => 10,
                    'parent_id' => 9,
                    'name' => 'WhatsArapp 1. Kur',
                    'slug' => 'whatsarapp-1-kur',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => false,
                ],
                [
                    'id' => 11,
                    'parent_id' => 9,
                    'name' => 'WhatsArapp 2. Kur',
                    'slug' => 'whatsarapp-2-kur',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => true,
                ],
                [
                    'id' => 12,
                    'parent_id' => 9,
                    'name' => 'WhatsArapp YDS',
                    'slug' => 'whatsarapp-yds',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => true,
                ],
                [
                    'id' => 13,
                    'parent_id' => 9,
                    'name' => 'WhatsArapp Kuran-i Kerim',
                    'slug' => 'whatsarapp-kurani-kerim',
                    'genders' => $genders,
                    'education_levels' => $educationLevels,
                    'has_admission_exam' => true,
                ],
            ]);
    }
}
