<?php

namespace Database\Seeders;

use App\Models\SideEffectCategory;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class SideEffectCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Nervous System',
                'translations' => [
                    'ar' => 'الجهاز العصبي',
                    'fr' => 'Système nerveux',
                ],
            ],
            [
                'name' => 'Immune System',
                'translations' => [
                    'ar' => 'الجهاز المناعي',
                    'fr' => 'Système immunitaire',
                ],
            ],
            [
                'name' => 'Respiratory System',
                'translations' => [
                    'ar' => 'الجهاز التنفسي',
                    'fr' => 'Système respiratoire',
                ],
            ],
            [
                'name' => 'Cardiovascular System',
                'translations' => [
                    'ar' => 'الجهاز القلبي الوعائي',
                    'fr' => 'Système cardiovasculaire',
                ],
            ],
            [
                'name' => 'Gastrointestinal System',
                'translations' => [
                    'ar' => 'الجهاز الهضمي',
                    'fr' => 'Système gastro-intestinal',
                ],
            ],
            [
                'name' => 'Endocrine System',
                'translations' => [
                    'ar' => 'الجهاز الغدد الصماء',
                    'fr' => 'Système endocrinien',
                ],
            ],
            [
                'name' => 'Musculoskeletal System',
                'translations' => [
                    'ar' => 'الجهاز العضلي الهيكلي',
                    'fr' => 'Système musculo-squelettique',
                ],
            ],
            [
                'name' => 'Urinary System',
                'translations' => [
                    'ar' => 'الجهاز البولي',
                    'fr' => 'Système urinaire',
                ],
            ],
            [
                'name' => 'Reproductive System',
                'translations' => [
                    'ar' => 'الجهاز التناسلي',
                    'fr' => 'Système reproducteur',
                ],
            ],
            [
                'name' => 'Integumentary System',
                'translations' => [
                    'ar' => 'الجهاز الجلدي',
                    'fr' => 'Système tégumentaire',
                ],
            ],
            [
                'name' => 'Lymphatic System',
                'translations' => [
                    'ar' => 'الجهاز اللمفاوي',
                    'fr' => 'Système lymphatique',
                ],
            ],
            [
                'name' => 'Sensory System',
                'translations' => [
                    'ar' => 'الجهاز الحسي',
                    'fr' => 'Système sensoriel',
                ],
            ],
            [
                'name' => 'Hematologic System',
                'translations' => [
                    'ar' => 'الجهاز الدموي',
                    'fr' => 'Système hématologique',
                ],
            ],
            [
                'name' => 'Dermatologic System',
                'translations' => [
                    'ar' => 'الجهاز الجلدي',
                    'fr' => 'Système dermatologique',
                ],
            ],
            [
                'name' => 'Ophthalmic System',
                'translations' => [
                    'ar' => 'الجهاز العيني',
                    'fr' => 'Système ophtalmique',
                ],
            ],
            [
                'name' => 'Endocrinology',
                'translations' => [
                    'ar' => 'الغدد الصماء',
                    'fr' => 'Endocrinologie',
                ],
            ],
            [
                'name' => 'Genetic Disorders',
                'translations' => [
                    'ar' => 'الاضطرابات الوراثية',
                    'fr' => 'Troubles génétiques',
                ],
            ],
            [
                'name' => 'Psychiatric Disorders',
                'translations' => [
                    'ar' => 'الاضطرابات النفسية',
                    'fr' => 'Troubles psychiatriques',
                ],
            ],
            [
                'name' => 'Metabolic Disorders',
                'translations' => [
                    'ar' => 'الاضطرابات الأيضية',
                    'fr' => 'Troubles métaboliques',
                ],
            ],
            [
                'name' => 'Oncology',
                'translations' => [
                    'ar' => 'علم الأورام',
                    'fr' => 'Oncologie',
                ],
            ],
            [
                'name' => 'Nephrology',
                'translations' => [
                    'ar' => 'أمراض الكلى',
                    'fr' => 'Néphrologie',
                ],
            ],
        ];


        foreach ($categories as $category) {
            $newCategory = SideEffectCategory::create([
                'name' => $category['name'],
            ]);

            foreach ($category['translations'] as $locale => $translatedName) {
                Translation::create([
                    'translatable_id' => $newCategory->id,
                    'translatable_type' => SideEffectCategory::class,
                    'locale' => $locale,
                    'field' => 'name',
                    'value' => $translatedName,
                ]);
            }
        }
    }
}
