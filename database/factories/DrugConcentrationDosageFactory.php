<?php

namespace Database\Factories;

use App\Models\Concentration;
use App\Models\Drug;
use App\Models\DrugConcentrationDosage;
use App\Models\RecommendedDosage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DrugConcentrationDosage>
 */
class DrugConcentrationDosageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quantity' => $this->faker->numberBetween(1, 100),
            'price' => $this->faker->randomFloat(2, 5, 100),
            'cost' => $this->faker->randomFloat(2, 1, 50),
            'barcode' => $this->faker->ean13(),
            'package_size' => $this->faker->word(),
            'expiry_date' => $this->faker->date(),
            'production_date' => $this->faker->date(),
            'image' => $this->faker->imageUrl(),
            'requires_prescription' => $this->faker->boolean(),
            'is_active' => $this->faker->boolean(),
            'drug_id' => Drug::inRandomOrder()->first()->id, // اختيار عشوائي للدواء
            'concentration_id' => Concentration::inRandomOrder()->first()->id, // اختيار عشوائي لتركيز الدواء
            'dosage_id' => RecommendedDosage::inRandomOrder()->first()->id, // اختيار عشوائي للجرعة
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (DrugConcentrationDosage $pivot) {
            // الحصول على دواء وتركيز عشوائيين
            $drug = Drug::inRandomOrder()->first();
            $concentration = Concentration::inRandomOrder()->first();

            // ربط الـ Drug و Concentration
            $pivot->drug_id = $drug->id;
            $pivot->concentration_id = $concentration->id;

            // لا حاجة لاستدعاء save() هنا لأننا نستخدم create في الفاكتوري
        });
    }

}
