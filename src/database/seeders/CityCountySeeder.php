<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CityCounty;
class CityCountySeeder extends Seeder
{
    /**
    * Run the database seeds.
    *
    * @return void
    */
    public function run()
    { 
        
        $json =  file_get_contents(__DIR__."../../jsons/city_counties.json");
        $data = json_decode($json);
        foreach ($data->data as   $item) {
            
            
            CityCounty::create([
                'city' => $item->city,
                'county' => $item->county                
                
            ]);
            
        }
    }
}
