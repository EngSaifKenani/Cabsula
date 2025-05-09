<?php

namespace Database\Factories;

use App\Models\ScientificName;
use App\Models\SideEffect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScientificName>
 */
class ScientificNameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        while (ScientificName::where('name', $name)->exists()) {
            $name = $this->faker->unique()->word();
        }

        return [
            'name' => $name,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($scientificName) {
            $scientificName->sideEffects()->attach(
                SideEffect::inRandomOrder()->take(2)->pluck('id')->toArray()
            );
        });
    }
}
