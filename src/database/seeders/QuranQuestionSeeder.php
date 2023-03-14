<?php

namespace Database\Seeders;

use App\Models\QuranQuestion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuranQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        QuranQuestion::factory()->count(rand(1, 100))->create();
    }
}
