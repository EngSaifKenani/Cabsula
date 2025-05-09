<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Concentration;
use App\Models\DrugConcentrationDosage;
use App\Models\Form;
use App\Models\Manufacturer;
use App\Models\ScientificName;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Drug>
 */
class DrugFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trade_name' =>  $this->faker->word(),

            'form_id' => Form::inRandomOrder()->first()->id,
            'manufacturer_id' => Manufacturer::inRandomOrder()->first()->id,
            'scientific_name_id' => ScientificName::inRandomOrder()->first()->id,
        ];
    }

}
