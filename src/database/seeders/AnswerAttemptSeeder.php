<?php

namespace Database\Seeders;

use App\Models\AnswerAttempt;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnswerAttemptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AnswerAttempt::factory()->count(rand(1, 100))->create();
    }
}
