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

        if (!app()->isProduction()) {
            $this->call(PeriodSeeder::class);
        }
    }
}
