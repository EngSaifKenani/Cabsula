<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SideEffectCategory>
 */
class SideEffectCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $namesEn = [
            'Neurological', 'Gastrointestinal', 'Cardiovascular',
            'Respiratory', 'Dermatological', 'Musculoskeletal',
            'Psychiatric', 'Hematological', 'Renal', 'Hepatic'
        ];

        $namesAr = [
            'أعراض عصبية', 'أعراض هضمية', 'أعراض قلبية',
            'أعراض تنفسية', 'أعراض جلدية', 'أعراض عضلية',
            'أعراض نفسية', 'أعراض دموية', 'أعراض كلوية', 'أعراض كبدية'
        ];

        $index = $this->faker->unique()->numberBetween(0, 9);
        return [
            'name' =>  $namesEn[$index],

        ];
    }
}
