<?php

namespace Database\Seeders;

use App\Models\RecommendedDosage;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class RecommendedDosageSeeder extends Seeder
{
    public function run(): void
    {
        $dosages = [
            [
                'dosage' => 'Take 1 tablet daily',
                'notes' => 'Usually taken in the morning.',
                'translations' => [
                    'ar' => [
                        'dosage' => 'تناول قرصًا واحدًا يوميًا',
                        'notes' => 'عادةً ما يُؤخذ في الصباح.',
                    ],
                    'fr' => [
                        'dosage' => 'Prendre 1 comprimé par jour',
                        'notes' => 'Habituellement pris le matin.',
                    ],
                ],
            ],
            [
                'dosage' => 'Apply twice a day',
                'notes' => 'Morning and evening',
                'translations' => [
                    'ar' => [
                        'dosage' => 'يُطبّق مرتين يوميًا',
                        'notes' => 'صباحًا ومساءً',
                    ],
                    'fr' => [
                        'dosage' => 'Appliquer deux fois par jour',
                        'notes' => 'Matin et soir',
                    ],
                ],
            ],
            [
                'dosage' => '5ml every 8 hours',
                'notes' => 'Do not exceed 3 doses per day.',
                'translations' => [
                    'ar' => [
                        'dosage' => '٥ مل كل ٨ ساعات',
                        'notes' => 'لا تتجاوز ثلاث جرعات في اليوم.',
                    ],
                    'fr' => [
                        'dosage' => '5ml toutes les 8 heures',
                        'notes' => 'Ne pas dépasser 3 doses par jour.',
                    ],
                ],
            ],
            [
                'dosage' => 'Use as needed',
                'notes' => 'Maximum 4 times a day.',
                'translations' => [
                    'ar' => [
                        'dosage' => 'يُستخدم عند الحاجة',
                        'notes' => 'بحد أقصى ٤ مرات يوميًا.',
                    ],
                    'fr' => [
                        'dosage' => 'Utiliser si nécessaire',
                        'notes' => 'Maximum 4 fois par jour.',
                    ],
                ],
            ],
        ];

        foreach ($dosages as $data) {
            $dosage = RecommendedDosage::create([
                'dosage' => $data['dosage'],
                'notes' => $data['notes'],
            ]);

            foreach ($data['translations'] as $locale => $fields) {
                foreach ($fields as $field => $value) {
                    Translation::create([
                        'translatable_id' => $dosage->id,
                        'translatable_type' => RecommendedDosage::class,
                        'locale' => $locale,
                        'field' => $field,
                        'value' => $value,
                    ]);
                }
            }
        }
    }
}
