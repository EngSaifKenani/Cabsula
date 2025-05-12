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
        'name' => 'name',
        'description' =>'description',
        'price' => $i*3+200,
        'image' => null,
        'cost' => $i*8+100,
        'profit_amount' => $i*3+200-$i*8-100,
        'stock' => 10,
        //'status' => $request['status'],
        'is_requires_prescription' => false,
        'production_date' => now(),
        'expiry_date' => now()->addDays(30),
        'admin_notes' => 'admin_notes',
        'form_id' => 3,
        'manufacturer_id' =>2,
        'recommended_dosage_id' =>1,
    ]);

}


    }
}
