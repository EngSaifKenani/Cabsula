<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\Manufacturer;
use App\Models\RecommendedDosage;
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
            // 'name' => 'name'.$i -> سيتم استبداله باسم دواء وهمي وفريد
            'name' => fake()->unique()->word() . ' ' . fake()->randomElement(['10mg', '25mg', '100mg', '250mg', '500mg']),

            // 'description' =>'description.$i' -> سيتم استبداله بجملة وصفية
            'description' => fake()->sentence(10),

            // 'image' => null -> يمكننا توليد رابط صورة وهمي أو تركه null
            'image' => fake()->optional()->imageUrl(640, 480, 'medicine'),

            // 'is_requires_prescription' => false -> سيتم جعله عشوائيًا (true or false)
            'is_requires_prescription' => fake()->boolean(75), // 75% of drugs will require a prescription

            // 'admin_notes' => 'admin_notes'.$i -> سيتم استبداله بملاحظات وهمية وقد يكون null أحيانًا
            'admin_notes' => fake()->optional()->paragraph(),

            // 'form_id' => 3 -> سيتم اختيار id عشوائي من جدول forms
            'form_id' => Form::inRandomOrder()->first()->id,

            // 'manufacturer_id' => 2 -> سيتم اختيار id عشوائي من جدول manufacturers
            'manufacturer_id' => Manufacturer::inRandomOrder()->first()->id,

            // 'recommended_dosage_id' => 1 -> سيتم اختيار id عشوائي من جدول recommended_dosages
            'recommended_dosage_id' => RecommendedDosage::inRandomOrder()->first()->id,
        ];
    }
}
