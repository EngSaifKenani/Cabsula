<?php

namespace Database\Seeders;

use App\Models\Drug;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DrugSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
for ($i=0; $i<20; $i++) {

    $drug = Drug::create([
        'name' => 'name'.$i,
        'description' =>'description.$i',
        'image' => null,
        'is_requires_prescription' => false,
        'admin_notes' => 'admin_notes'.$i,
        'form_id' => 3,
        'manufacturer_id' =>2,
        'recommended_dosage_id' =>1,
    ]);

}


    }
}
