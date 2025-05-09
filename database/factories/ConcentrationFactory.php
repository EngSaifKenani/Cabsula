<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Concentration>
 */
class ConcentrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'value' => $this->faker->randomFloat(2, 0.1, 100), // قيمة عشوائية بين 0.1 و 100
            'unit' => $this->faker->randomElement(['mg', 'g', 'ml', 'l']), // وحدة قياس عشوائية
            'description' => $this->faker->sentence(),

        ];
    }
}
