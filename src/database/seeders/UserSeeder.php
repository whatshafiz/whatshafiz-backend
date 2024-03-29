<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::factory()
            ->completed()
            ->create(['phone_number' => '905413582616', 'password' => bcrypt('12345678')]);
        $user->assignRole('Admin');
        User::factory()->count(rand(10, 100))->create();
        User::factory()->completed()->count(rand(10, 100))->create();
    }
}
