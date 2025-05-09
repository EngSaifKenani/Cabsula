<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecommendedDosage>
 */
class RecommendedDosageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // قائمة الجرعات باللغة الإنجليزية
        $dosages = [
            '1 tablet daily', '2 tablets twice a day', '5ml every 6 hours',
            '1 capsule before sleep', '10 drops in water', 'Apply twice daily',
            '1 puff every 4 hours', 'Use as needed', '1 suppository at night',
            'Take with food', '2 sprays per nostril', 'Use topically',
            'Inject once a week', '3 tablets after meals', 'One spoonful daily',
            'Half tablet in morning', 'One patch per day', 'One chewable tablet',
            'Instill 2 drops in each eye', 'Swish and spit twice a day'
        ];

        // الترجمة العربية المقابلة
        $dosagesAr = [
            'قرص واحد يومياً', 'قرصان مرتان يومياً', '5 مل كل 6 ساعات',
            'كبسولة قبل النوم', '10 قطرات في الماء', 'يُستخدم مرتين يومياً',
            'بخة كل 4 ساعات', 'حسب الحاجة', 'تحميلة ليلية',
            'تناول مع الطعام', 'بختان في كل فتحة أنف', 'للاستخدام الخارجي',
            'حقنة أسبوعياً', '3 أقراص بعد الطعام', 'ملعقة يومياً',
            'نصف قرص صباحاً', 'لاصقة يومياً', 'قرص مضغ',
            'نقطتان في كل عين', 'مضمضة مرتين يومياً'
        ];

        $dosageIndex = array_rand($dosages);
        $dosage = $dosages[$dosageIndex];
        $dosageAr = $dosagesAr[$dosageIndex];
        unset($dosages[$dosageIndex]);
        unset($dosagesAr[$dosageIndex]);

        return [
            'dosage' => $dosage,
            'notes' => $this->faker->sentence(),

        ];
    }
}
