<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cityNames = json_decode(File::get(database_path('data/cities.json')), true);
        $turkiyeId = Country::where('name', 'Türkiye')->value('id');

        foreach ($cityNames as $cityName) {
            City::create(['country_id' => $turkiyeId, 'name' => $cityName]);
        }
    }
}
