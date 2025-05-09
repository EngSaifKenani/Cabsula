<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        $forms = [
            [
                'name' => 'Capsule',
                'translations' => [
                    ['locale' => 'ar', 'value' => 'كبسولة'],
                    ['locale' => 'fr', 'value' => 'Gélule'],
                ],
            ],
            [
                'name' => 'Syrup',
                'translations' => [
                    ['locale' => 'ar', 'value' => 'شراب'],
                    ['locale' => 'fr', 'value' => 'Sirop'],
                ],
            ],
            [
                'name' => 'Drop',
                'translations' => [
                    ['locale' => 'ar', 'value' => 'قطرة'],
                    ['locale' => 'fr', 'value' => 'Goutte'],
                ],
            ],
            [
                'name' => 'Ointment',
                'translations' => [
                    ['locale' => 'ar', 'value' => 'مرهم'],
                    ['locale' => 'fr', 'value' => 'Pommade'],
                ],
            ],
            [
                'name' => 'Tablet',
                'translations' => [
                    ['locale' => 'ar', 'value' => 'قرص'],
                    ['locale' => 'fr', 'value' => 'Comprimé'],
                ],
            ],
        ];

        foreach ($forms as $formData) {
            $form = Form::create([
                'name' => $formData['name'],
            ]);

            foreach ($formData['translations'] as $translation) {
                Translation::create([
                    'translatable_id' => $form->id,
                    'translatable_type' => Form::class,
                    'locale' => $translation['locale'],
                    'field' => 'name',
                    'value' => $translation['value'],
                ]);
            }
        }
    }
}
