<?php

namespace Database\Seeders;

use App\Models\WhatsappMessengerNumber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WhatsappMessengerNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        WhatsappMessengerNumber::factory()->count(rand(1, 7))->create();
    }
}
