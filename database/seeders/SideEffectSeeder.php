<?php

namespace Database\Seeders;

use App\Models\SideEffect;
use App\Models\SideEffectCategory;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class SideEffectSeeder extends Seeder
{
    public function run(): void
    {
        // الحصول على جميع الفئات
        $categories = SideEffectCategory::all();

        $sideEffectsData = [
            'Nervous System' => [
                [
                    'name' => 'Dizziness',
                    'translations' => [
                        'ar' => 'دوار',
                        'fr' => 'Vertige',
                    ],
                ],
                [
                    'name' => 'Headache',
                    'translations' => [
                        'ar' => 'صداع',
                        'fr' => 'Céphalée',
                    ],
                ],
                [
                    'name' => 'Nausea',
                    'translations' => [
                        'ar' => 'غثيان',
                        'fr' => 'Nausée',
                    ],
                ],
                [
                    'name' => 'Fatigue',
                    'translations' => [
                        'ar' => 'إرهاق',
                        'fr' => 'Fatigue',
                    ],
                ],
                [
                    'name' => 'Insomnia',
                    'translations' => [
                        'ar' => 'أرق',
                        'fr' => 'Insomnie',
                    ],
                ],
                [
                    'name' => 'Tremors',
                    'translations' => [
                        'ar' => 'ارتعاش',
                        'fr' => 'Tremblements',
                    ],
                ],
            ],
            'Immune System' => [
                [
                    'name' => 'Allergic Reaction',
                    'translations' => [
                        'ar' => 'رد فعل تحسسي',
                        'fr' => 'Réaction allergique',
                    ],
                ],
                [
                    'name' => 'Fever',
                    'translations' => [
                        'ar' => 'حمى',
                        'fr' => 'Fièvre',
                    ],
                ],
                [
                    'name' => 'Swelling',
                    'translations' => [
                        'ar' => 'تورم',
                        'fr' => 'Gonflement',
                    ],
                ],
                [
                    'name' => 'Rash',
                    'translations' => [
                        'ar' => 'طفح جلدي',
                        'fr' => 'Éruption cutanée',
                    ],
                ],
                [
                    'name' => 'Fatigue',
                    'translations' => [
                        'ar' => 'إرهاق',
                        'fr' => 'Fatigue',
                    ],
                ],
                [
                    'name' => 'Itching',
                    'translations' => [
                        'ar' => 'حكة',
                        'fr' => 'Démangeaison',
                    ],
                ],
            ],
            // إضافة باقي الفئات هنا...
        ];

        // تنفيذ الـ seeder
        foreach ($sideEffectsData as $categoryName => $sideEffects) {
            $category = SideEffectCategory::where('name', $categoryName)->first();

            if ($category) {
                // إنشاء الأعراض الجانبية داخل الفئة
                foreach ($sideEffects as $sideEffectData) {
                    $newSideEffect = SideEffect::create([
                        'category_id' => $category->id,
                        'name' => $sideEffectData['name'],
                    ]);

                    // إضافة الترجمات للأعراض الجانبية
                    foreach ($sideEffectData['translations'] as $locale => $translatedName) {
                        Translation::create([
                            'translatable_id' => $newSideEffect->id,
                            'translatable_type' => SideEffect::class,
                            'locale' => $locale,
                            'field' => 'name',
                            'value' => $translatedName,
                        ]);
                    }
                }
            }
        }
    }
}
