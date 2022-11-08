<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UniversityList;
class UniversityListSeeder extends Seeder
{
    /**
    * Run the database seeds.
    *
    * @return void
    */
    public function run()
    {
        $json =  file_get_contents(__DIR__."../../jsons/universities.json");
        $data = json_decode($json);
        foreach ($data->data as   $item) {
            
            UniversityList::create([
                'name' => $item->uni_name,
                'faculties' => json_encode($item->uni_fak),
                'junior_faculties' => $item->uni_myo !== null?json_encode($item->uni_myo):NULL,
                'location' => $item->uni_sehir,
                
            ]);
            
        }
    }
}
