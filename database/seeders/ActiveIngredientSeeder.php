<?php

namespace Database\Seeders;

use App\Models\ActiveIngredient;
use App\Models\SideEffect;
use App\Models\SideEffectCategory;
use App\Models\TherapeuticUse;
use App\Models\Translation;
use App\Services\TranslationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActiveIngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            [
                'scientific_name' => 'Paracetamol',
                'trade_name' => 'Tylenol',
                'description' => 'Used to treat pain and fever.',
                'cas_number' => '103-90-2',
                'unii_code' => 'X4J0H1S45F',
                'is_active' => true,
                'side_effects' => [
                    'Nausea', 'Dizziness', 'Rash'
                ],
                'therapeutic_uses' => [
                    [
                        'id' => 1,
                        'name' => 'Pain Reliever',
                        'translations' => [
                            'ar' => 'مسكن للألم',
                            'fr' => 'Antidouleur',
                        ],
                        'description' => 'Used to relieve mild to moderate pain such as headaches, muscle aches, or menstrual cramps.',
                        'description_translations' => [
                            'ar' => 'يستخدم لتخفيف الألم الخفيف إلى المتوسط مثل الصداع، آلام العضلات، أو التقلصات الشهرية.',
                            'fr' => 'Utilisé pour soulager la douleur légère à modérée comme les maux de tête, les douleurs musculaires ou les crampes menstruelles.',
                        ]
                    ],
                    [
                        'id' => 2,
                        'name' => 'Fever Reducer',
                        'translations' => [
                            'ar' => 'خافض للحرارة',
                            'fr' => 'Réducteur de fièvre',
                        ],
                        'description' => 'Helps to lower high body temperature caused by infections or other medical conditions.',
                        'description_translations' => [
                            'ar' => 'يساعد على خفض درجة حرارة الجسم المرتفعة الناتجة عن العدوى أو الحالات الطبية الأخرى.',
                            'fr' => 'Aide à abaisser la température corporelle élevée causée par des infections ou d\'autres conditions médicales.',
                        ]
                    ]
                ],
                'translations' => [
                    'en' => ['scientific_name' => 'Paracetamol', 'trade_name' => 'Tylenol', 'description' => 'Used to treat pain and fever.'],
                    'ar' => ['scientific_name' => 'باراسيتامول', 'trade_name' => 'تايلينول', 'description' => 'يستخدم لعلاج الألم والحمى.'],
                    'fr' => ['scientific_name' => 'Paracétamol', 'trade_name' => 'Tylenol', 'description' => 'Utilisé pour traiter la douleur et la fièvre.'],
                ]
            ],
            [
                'scientific_name' => 'Ibuprofen',
                'trade_name' => 'Advil',
                'description' => 'Used to relieve pain and inflammation.',
                'cas_number' => '15687-27-1',
                'unii_code' => 'IH0D4XIV8I',
                'is_active' => true,
                'side_effects' => [
                    'Nausea', 'Headache', 'Dizziness'
                ],
                'therapeutic_uses' => [
                    [
                        'id' => 1,
                        'name' => 'Pain Reliever',
                        'translations' => [
                            'ar' => 'مسكن للألم',
                            'fr' => 'Antidouleur',
                        ],
                        'description' => 'Used to relieve pain from conditions such as arthritis, muscle aches, and menstrual cramps.',
                        'description_translations' => [
                            'ar' => 'يستخدم لتخفيف الألم الناتج عن حالات مثل التهاب المفاصل، آلام العضلات، والتقلصات الشهرية.',
                            'fr' => 'Utilisé pour soulager la douleur provenant de conditions telles que l\'arthrite, les douleurs musculaires et les crampes menstruelles.',
                        ]
                    ],
                    [
                        'id' => 2,
                        'name' => 'Anti-Inflammatory',
                        'translations' => [
                            'ar' => 'مضاد للالتهابات',
                            'fr' => 'Anti-inflammatoire',
                        ],
                        'description' => 'Helps reduce inflammation and swelling caused by injury or infection.',
                        'description_translations' => [
                            'ar' => 'يساعد في تقليل الالتهاب والتورم الناتج عن الإصابة أو العدوى.',
                            'fr' => 'Aide à réduire l\'inflammation et le gonflement causés par une blessure ou une infection.',
                        ]
                    ]
                ],
                'translations' => [
                    'en' => ['scientific_name' => 'Ibuprofen', 'trade_name' => 'Advil', 'description' => 'Used to relieve pain and inflammation.'],
                    'ar' => ['scientific_name' => 'إيبوبروفين', 'trade_name' => 'أدفيل', 'description' => 'يستخدم لتخفيف الألم والالتهابات.'],
                    'fr' => ['scientific_name' => 'Ibuprofène', 'trade_name' => 'Advil', 'description' => 'Utilisé pour soulager la douleur et l\'inflammation.'],
                ]
            ],
            [
                'scientific_name' => 'Aspirin',
                'trade_name' => 'Bayer',
                'description' => 'Used for pain relief, anti-inflammatory, and blood thinning.',
                'cas_number' => '50-78-2',
                'unii_code' => 'R16QZP7X48',
                'is_active' => true,
                'side_effects' => [
                    'Stomach upset', 'Nausea', 'Bleeding'
                ],
                'therapeutic_uses' => [
                    [
                        'id' => 1,
                        'name' => 'Pain Reliever',
                        'translations' => [
                            'ar' => 'مسكن للألم',
                            'fr' => 'Antidouleur',
                        ],
                        'description' => 'Used for relieving mild to moderate pain such as headaches, muscle aches, or arthritis pain.',
                        'description_translations' => [
                            'ar' => 'يستخدم لتخفيف الألم الخفيف إلى المتوسط مثل الصداع، آلام العضلات، أو ألم التهاب المفاصل.',
                            'fr' => 'Utilisé pour soulager la douleur légère à modérée comme les maux de tête, les douleurs musculaires ou la douleur liée à l\'arthrite.',
                        ]
                    ],
                    [
                        'id' => 2,
                        'name' => 'Anti-Inflammatory',
                        'translations' => [
                            'ar' => 'مضاد للالتهابات',
                            'fr' => 'Anti-inflammatoire',
                        ],
                        'description' => 'Used to reduce inflammation and swelling caused by arthritis or injury.',
                        'description_translations' => [
                            'ar' => 'يستخدم لتقليل الالتهاب والتورم الناتج عن التهاب المفاصل أو الإصابة.',
                            'fr' => 'Utilisé pour réduire l\'inflammation et le gonflement causés par l\'arthrite ou une blessure.',
                        ]
                    ],
                    [
                        'id' => 3,
                        'name' => 'Blood Thinner',
                        'translations' => [
                            'ar' => 'مميع للدم',
                            'fr' => 'Anticoagulant',
                        ],
                        'description' => 'Helps prevent blood clots from forming, often used in cardiovascular conditions.',
                        'description_translations' => [
                            'ar' => 'يساعد على منع تكوّن جلطات الدم، ويستخدم غالبًا في الحالات القلبية الوعائية.',
                            'fr' => 'Aide à prévenir la formation de caillots sanguins, souvent utilisé dans les maladies cardiovasculaires.',
                        ]
                    ]
                ],
                'translations' => [
                    'en' => ['scientific_name' => 'Aspirin', 'trade_name' => 'Bayer', 'description' => 'Used for pain relief, anti-inflammatory, and blood thinning.'],
                    'ar' => ['scientific_name' => 'أسبرين', 'trade_name' => 'باير', 'description' => 'يستخدم لتخفيف الألم، مضاد للالتهابات، وتخفيف الدم.'],
                    'fr' => ['scientific_name' => 'Aspirine', 'trade_name' => 'Bayer', 'description' => 'Utilisé pour soulager la douleur, anti-inflammatoire et pour fluidifier le sang.'],
                ]
            ],
            [
                'scientific_name' => 'Amoxicillin',
                'trade_name' => 'Amoxil',
                'description' => 'Used to treat bacterial infections.',
                'cas_number' => '26787-78-0',
                'unii_code' => '72A3H9V38C',
                'is_active' => true,
                'side_effects' => [
                    'Rash', 'Diarrhea', 'Nausea'
                ],
                'therapeutic_uses' => [
                    ['id' => 4, 'is_popular' => true]
                ],
                'translations' => [
                    'en' => ['scientific_name' => 'Amoxicillin', 'trade_name' => 'Amoxil', 'description' => 'Used to treat bacterial infections.'],
                    'ar' => ['scientific_name' => 'أموكسيسيلين', 'trade_name' => 'أموكسيل', 'description' => 'يستخدم لعلاج العدوى البكتيرية.'],
                    'fr' => ['scientific_name' => 'Amoxicilline', 'trade_name' => 'Amoxil', 'description' => 'Utilisé pour traiter les infections bactériennes.'],
                ]
            ],
            [
                'scientific_name' => 'Diphenhydramine',
                'trade_name' => 'Benadryl',
                'description' => 'Used to treat allergies and insomnia.',
                'cas_number' => '58-73-1',
                'unii_code' => 'W6JBB6U1FT',
                'is_active' => true,
                'side_effects' => [
                    'Drowsiness', 'Dry mouth', 'Dizziness'
                ],
                'therapeutic_uses' => [
                    ['id' => 5, 'is_popular' => true]
                ],
                'translations' => [
                    'en' => ['scientific_name' => 'Diphenhydramine', 'trade_name' => 'Benadryl', 'description' => 'Used to treat allergies and insomnia.'],
                    'ar' => ['scientific_name' => 'ديفين هيدرامين', 'trade_name' => 'بينادريل', 'description' => 'يستخدم لعلاج الحساسية والأرق.'],
                    'fr' => ['scientific_name' => 'Diphenhydramine', 'trade_name' => 'Benadryl', 'description' => 'Utilisé pour traiter les allergies et l\'insomnie.'],
                ]
            ],
            [
                'scientific_name' => 'Cetirizine',
                'trade_name' => 'Zyrtec',
                'description' => 'Used to treat allergies and hay fever.',
                'cas_number' => '83881-51-0',
                'unii_code' => 'X3K3D207B2',
                'is_active' => true,
                'side_effects' => [
                    'Drowsiness', 'Dry mouth', 'Headache'
                ],
                'therapeutic_uses' => [
                    ['id' => 6, 'is_popular' => true]
                ],
                'translations' => [
                    'en' => ['scientific_name' => 'Cetirizine', 'trade_name' => 'Zyrtec', 'description' => 'Used to treat allergies and hay fever.'],
                    'ar' => ['scientific_name' => 'سيتريزين', 'trade_name' => 'زيرتك', 'description' => 'يستخدم لعلاج الحساسية وحمى القش.'],
                    'fr' => ['scientific_name' => 'Cétirizine', 'trade_name' => 'Zyrtec', 'description' => 'Utilisé pour traiter les allergies et la rhinite allergique.'],
                ]
            ],
            [
                'scientific_name' => 'Prednisone',
                'trade_name' => 'Deltasone',
                'description' => 'Used to reduce inflammation and treat conditions such as arthritis.',
                'cas_number' => '53-03-2',
                'unii_code' => 'YV2P9P16GR',
                'is_active' => true,
                'side_effects' => [
                    'Weight gain', 'High blood pressure', 'Mood changes'
                ],
                'therapeutic_uses' => [
                    ['id' => 7, 'is_popular' => true]
                ],
                'translations' => [
                    'en' => ['scientific_name' => 'Prednisone', 'trade_name' => 'Deltasone', 'description' => 'Used to reduce inflammation and treat conditions such as arthritis.'],
                    'ar' => ['scientific_name' => 'بريدنيزون', 'trade_name' => 'دلتا سون', 'description' => 'يستخدم لتقليل الالتهاب وعلاج حالات مثل التهاب المفاصل.'],
                    'fr' => ['scientific_name' => 'Prednisone', 'trade_name' => 'Deltasone', 'description' => 'Utilisé pour réduire l\'inflammation et traiter des affections telles que l\'arthrite.'],
                ]
            ],
            [
                'scientific_name' => 'Loratadine',
                'trade_name' => 'Claritin',
                'description' => 'Used to treat allergy symptoms.',
                'cas_number' => '79794-75-5',
                'unii_code' => 'M8WGB3I53E',
                'is_active' => true,
                'side_effects' => [
                    'Headache', 'Dry mouth', 'Drowsiness'
                ],
                'therapeutic_uses' => [
                    ['id' => 8, 'is_popular' => true]
                ],
                'translations' => [
                    'en' => ['scientific_name' => 'Loratadine', 'trade_name' => 'Claritin', 'description' => 'Used to treat allergy symptoms.'],
                    'ar' => ['scientific_name' => 'لوراتادين', 'trade_name' => 'كلاريتن', 'description' => 'يستخدم لعلاج أعراض الحساسية.'],
                    'fr' => ['scientific_name' => 'Loratadine', 'trade_name' => 'Claritin', 'description' => 'Utilisé pour traiter les symptômes d\'allergies.'],
                ]
            ],
            [
                'scientific_name' => 'Simvastatin',
                'trade_name' => 'Zocor',
                'description' => 'Used to lower cholesterol and reduce the risk of heart disease.',
                'cas_number' => '79902-63-9',
                'unii_code' => 'Z6GDD8X8O4',
                'is_active' => true,
                'side_effects' => [
                    'Muscle pain', 'Liver damage', 'Nausea'
                ],
                'therapeutic_uses' => [
                    ['id' => 9, 'is_popular' => true]
                ],
                'translations' => [
                    'en' => ['scientific_name' => 'Simvastatin', 'trade_name' => 'Zocor', 'description' => 'Used to lower cholesterol and reduce the risk of heart disease.'],
                    'ar' => ['scientific_name' => 'سيمفاستاتين', 'trade_name' => 'زوكور', 'description' => 'يستخدم لخفض الكوليسترول وتقليل خطر الإصابة بأمراض القلب.'],
                    'fr' => ['scientific_name' => 'Simvastatine', 'trade_name' => 'Zocor', 'description' => 'Utilisé pour abaisser le cholestérol et réduire le risque de maladies cardiaques.'],
                ]
            ],
            [
                'scientific_name' => 'Furosemide',
                'trade_name' => 'Lasix',
                'description' => 'Used to treat edema and hypertension.',
                'cas_number' => '54-31-9',
                'unii_code' => 'M2VNY7KM8J',
                'is_active' => true,
                'side_effects' => [
                    'Dehydration', 'Dizziness', 'Low blood pressure'
                ],
                'therapeutic_uses' => [
                    ['id' => 10, 'is_popular' => true]
                ],
                'translations' => [
                    'en' => ['scientific_name' => 'Furosemide', 'trade_name' => 'Lasix', 'description' => 'Used to treat edema and hypertension.'],
                    'ar' => ['scientific_name' => 'فوروسيميد', 'trade_name' => 'لاسيكس', 'description' => 'يستخدم لعلاج الوذمة وارتفاع ضغط الدم.'],
                    'fr' => ['scientific_name' => 'Furosémide', 'trade_name' => 'Lasix', 'description' => 'Utilisé pour traiter l\'œdème et l\'hypertension.'],
                ]
            ]
        ];


        foreach ($ingredients as $ingredientData) {
            DB::transaction(function () use ($ingredientData) {
                // Create Active Ingredient
                $activeIngredient = ActiveIngredient::create([
                    'scientific_name' => $ingredientData['scientific_name'],
                    'description' => $ingredientData['description'],
                    'cas_number' => $ingredientData['cas_number'],
                    'unii_code' => $ingredientData['unii_code'],
                    'is_active' => $ingredientData['is_active'],
                ]);

                // Add translations
                foreach ($ingredientData['translations'] as $locale => $translation) {
                    foreach ($translation as $field => $value) {
                        if ($field == 'trade_name') continue;
                        Translation::create([
                            'translatable_id' => $activeIngredient->id,
                            'translatable_type' => ActiveIngredient::class,
                            'locale' => $locale,
                            'field' => $field,
                            'value' => $value,
                        ]);
                    }
                }

                // Add Side Effects
                $sideEffects = SideEffect::whereIn('name', $ingredientData['side_effects'])->get();
                $activeIngredient->sideEffects()->sync($sideEffects->pluck('id'));

                // Add Therapeutic Uses
                $therapeuticUses = [];
                foreach ($ingredientData['therapeutic_uses'] as $use) {
                    $therapeuticUses[$use['id']] = [
                        'is_popular' => $use['is_popular'] ?? false, // استخدام القيمة الافتراضية false في حال لم يكن المفتاح موجودًا
                    ];
                }

                $activeIngredient->therapeuticUses()->sync($therapeuticUses);
            });
        }
    }
}
