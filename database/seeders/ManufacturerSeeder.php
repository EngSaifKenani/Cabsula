<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class ManufacturerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $manufacturers = [
            [
                'name' => 'Roche',
                'country' => 'Switzerland',
                'website' => 'https://www.roche.com',
                'translations' => [
                    ['locale' => 'ar', 'name' => 'روش', 'country' => 'سويسرا'],
                    ['locale' => 'fr', 'name' => 'Roche', 'country' => 'Suisse'],
                ],
            ],
            [
                'name' => 'Johnson & Johnson',
                'country' => 'United States',
                'website' => 'https://www.jnj.com',
                'translations' => [
                    ['locale' => 'ar', 'name' => 'جونسون آند جونسون', 'country' => 'الولايات المتحدة'],
                    ['locale' => 'fr', 'name' => 'Johnson & Johnson', 'country' => 'États-Unis'],
                ],
            ],
            [
                'name' => 'Merck & Co.',
                'country' => 'United States',
                'website' => 'https://www.merck.com',
                'translations' => [
                    ['locale' => 'ar', 'name' => 'ميرك آند كو', 'country' => 'الولايات المتحدة'],
                    ['locale' => 'fr', 'name' => 'Merck & Co.', 'country' => 'États-Unis'],
                ],
            ],
            [
                'name' => 'Sanofi',
                'country' => 'France',
                'website' => 'https://www.sanofi.com',
                'translations' => [
                    ['locale' => 'ar', 'name' => 'سانوفي', 'country' => 'فرنسا'],
                    ['locale' => 'fr', 'name' => 'Sanofi', 'country' => 'France'],
                ],
            ],
            [
                'name' => 'GlaxoSmithKline (GSK)',
                'country' => 'United Kingdom',
                'website' => 'https://www.gsk.com',
                'translations' => [
                    ['locale' => 'ar', 'name' => 'جلاكسميثكلاين', 'country' => 'المملكة المتحدة'],
                    ['locale' => 'fr', 'name' => 'GlaxoSmithKline', 'country' => 'Royaume-Uni'],
                ],
            ],
            [
                'name' => 'AstraZeneca',
                'country' => 'United Kingdom',
                'website' => 'https://www.astrazeneca.com',
                'translations' => [
                    ['locale' => 'ar', 'name' => 'أسترازينيكا', 'country' => 'المملكة المتحدة'],
                    ['locale' => 'fr', 'name' => 'AstraZeneca', 'country' => 'Royaume-Uni'],
                ],
            ],
            [
                'name' => 'Bayer',
                'country' => 'Germany',
                'website' => 'https://www.bayer.com',
                'translations' => [
                    ['locale' => 'ar', 'name' => 'باير', 'country' => 'ألمانيا'],
                    ['locale' => 'fr', 'name' => 'Bayer', 'country' => 'Allemagne'],
                ],
            ],
            [
                'name' => 'Moderna',
                'country' => 'United States',
                'website' => 'https://www.modernatx.com',
                'translations' => [
                    ['locale' => 'ar', 'name' => 'موديرنا', 'country' => 'الولايات المتحدة'],
                    ['locale' => 'fr', 'name' => 'Moderna', 'country' => 'États-Unis'],
                ],
            ],
        ];

        foreach ($manufacturers as $manufacturerData) {
            $manufacturer = Manufacturer::create([
                'name' => $manufacturerData['name'],
                'country' => $manufacturerData['country'],
                'website' => $manufacturerData['website'],
            ]);

            foreach ($manufacturerData['translations'] as $translation) {
                Translation::create([
                    'translatable_id' => $manufacturer->id,
                    'translatable_type' => Manufacturer::class,
                    'locale' => $translation['locale'],
                    'field' => 'name',
                    'value' => $translation['name'],
                ]);

                Translation::create([
                    'translatable_id' => $manufacturer->id,
                    'translatable_type' => Manufacturer::class,
                    'locale' => $translation['locale'],
                    'field' => 'country',
                    'value' => $translation['country'],
                ]);
            }
        }
    }
}
