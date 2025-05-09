<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Form>
 */
class FormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),  // مجرد نص عادي بدلاً من مصفوفة
            'description' => $this->faker->sentence(),  // مجرد نص عادي بدلاً من مصفوفة
            'image' => 'form_' . $this->faker->numberBetween(1, 10) . '.png', // اسم صورة وهمي
        ];
    }
}
