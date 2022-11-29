<?php

namespace Database\Seeders;

use App\Models\WhatsappGroup;
use App\Models\WhatsappGroupUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WhatsappGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        WhatsappGroup::factory()->count(rand(1, 100))->create();
        WhatsappGroupUser::factory()->count(rand(1, 100))->create();
    }
}
