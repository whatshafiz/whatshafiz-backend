<?php

namespace Database\Seeders;

use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Period::factory()->count(rand(1, 100))->create(['can_be_applied' => false]);
        Period::factory()
            ->hafizol()
            ->create(['can_be_applied' => true, 'can_be_applied_until' => Carbon::now()->addMonths(rand(1, 3))]);
        Period::factory()
            ->hafizkal()
            ->create(['can_be_applied' => true, 'can_be_applied_until' => Carbon::now()->addMonths(rand(1, 3))]);
    }
}
