<?php

namespace Database\Seeders;

use App\Models\Concentration;
use App\Models\Translation;
use Illuminate\Database\Seeder;
use App\Services\TranslationService;

class ConcentrationSeeder extends Seeder
{
    public function run(TranslationService $translationService)
    {
        $sourceLocale = 'en';  // نحدد اللغة الأصلية

        // بيانات التركيزات التي نريد إدخالها
        $concentrations = [
            [
                'value' => 50,
                'unit' => 'mg',
                'description' => 'A high concentration of the drug',
                'translations' => [
                    'ar' => [
                        'name' => 'تركيز عالٍ من الدواء',
                        'description' => 'تركيز عالٍ من الدواء'
                    ],
                    'fr' => [
                        'name' => 'Haute concentration du médicament',
                        'description' => 'Haute concentration du médicament'
                    ]
                ]
            ],
            [
                'value' => 100,
                'unit' => 'mg',
                'description' => 'Double concentration of the drug',
                'translations' => [
                    'ar' => [
                        'name' => 'تركيز مزدوج من الدواء',
                        'description' => 'تركيز مزدوج من الدواء'
                    ],
                    'fr' => [
                        'name' => 'Double concentration du médicament',
                        'description' => 'Double concentration du médicament'
                    ]
                ]
            ]
        ];

        foreach ($concentrations as $concentrationData) {
            // إنشاء التركيز
            $concentration = Concentration::create([
                'value' => $concentrationData['value'],
                'unit' => $concentrationData['unit'],
                'description' => $concentrationData['description']
            ]);

            // إضافة الترجمة للغات المختلفة
            foreach ($concentrationData['translations'] as $locale => $translation) {
                // ترجمة الاسم
                $concentration->translations()->create([
                    'locale' => $locale,
                    'field' => 'name',
                    'value' => $translation['name']
                ]);

                // ترجمة الوصف
                $concentration->translations()->create([
                    'locale' => $locale,
                    'field' => 'description',
                    'value' => $translation['description']
                ]);
            }
        }
    }
}
