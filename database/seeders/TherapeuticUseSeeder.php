<?php

namespace Database\Seeders;

use App\Models\TherapeuticUse;
use App\Models\Translation;
use App\Services\TranslationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TherapeuticUseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $uses = [
            [
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
            ],
            [
                'name' => 'Anti-inflammatory',
                'translations' => [
                    'ar' => 'مضاد للالتهابات',
                    'fr' => 'Anti-inflammatoire',
                ],
                'description' => 'Reduces inflammation and swelling associated with conditions like arthritis or injury.',
                'description_translations' => [
                    'ar' => 'يقلل من الالتهاب والتورم المرتبطين بحالات مثل التهاب المفاصل أو الإصابات.',
                    'fr' => 'Réduit l\'inflammation et le gonflement associés à des conditions telles que l\'arthrite ou les blessures.',
                ]
            ],
            [
                'name' => 'Cough Suppressant',
                'translations' => [
                    'ar' => 'مخفف للسعال',
                    'fr' => 'Antitussif',
                ],
                'description' => 'Used to relieve dry or irritating coughs by suppressing the cough reflex.',
                'description_translations' => [
                    'ar' => 'يستخدم لتخفيف السعال الجاف أو المزعج عن طريق قمع منعكس السعال.',
                    'fr' => 'Utilisé pour soulager les toux sèches ou irritantes en supprimant le réflexe de la toux.',
                ]
            ],
            [
                'name' => 'Antacid',
                'translations' => [
                    'ar' => 'مضاد للحموضة',
                    'fr' => 'Antiacide',
                ],
                'description' => 'Neutralizes stomach acid to relieve heartburn, indigestion, and upset stomach.',
                'description_translations' => [
                    'ar' => 'يعمل على تحييد الحمض في المعدة لتخفيف حرقة المعدة، عسر الهضم، واضطرابات المعدة.',
                    'fr' => 'Neutralise l\'acide gastrique pour soulager les brûlures d\'estomac, l\'indigestion et les maux d\'estomac.',
                ]
            ],
            [
                'name' => 'Antibiotic',
                'translations' => [
                    'ar' => 'مضاد حيوي',
                    'fr' => 'Antibiotique',
                ],
                'description' => 'Used to treat bacterial infections by killing or inhibiting the growth of bacteria.',
                'description_translations' => [
                    'ar' => 'يستخدم لعلاج العدوى البكتيرية عن طريق قتل البكتيريا أو منع نموها.',
                    'fr' => 'Utilisé pour traiter les infections bactériennes en tuant ou en inhibant la croissance des bactéries.',
                ]
            ],
            [
                'name' => 'Antidepressant',
                'translations' => [
                    'ar' => 'مضاد للاكتئاب',
                    'fr' => 'Antidépresseur',
                ],
                'description' => 'Used to treat depression by improving mood and emotional state.',
                'description_translations' => [
                    'ar' => 'يستخدم لعلاج الاكتئاب من خلال تحسين المزاج والحالة العاطفية.',
                    'fr' => 'Utilisé pour traiter la dépression en améliorant l\'humeur et l\'état émotionnel.',
                ]
            ],
            [
                'name' => 'Antihistamine',
                'translations' => [
                    'ar' => 'مضاد للهستامين',
                    'fr' => 'Antihistaminique',
                ],
                'description' => 'Used to treat allergic reactions by blocking the effects of histamine.',
                'description_translations' => [
                    'ar' => 'يستخدم لعلاج ردود الفعل التحسسية من خلال حجب آثار الهستامين.',
                    'fr' => 'Utilisé pour traiter les réactions allergiques en bloquant les effets de l\'histamine.',
                ]
            ],
            [
                'name' => 'Diuretic',
                'translations' => [
                    'ar' => 'مدر للبول',
                    'fr' => 'Diurétique',
                ],
                'description' => 'Helps to remove excess salt and water from the body by increasing urine production.',
                'description_translations' => [
                    'ar' => 'يساعد في إزالة الملح والماء الزائد من الجسم عن طريق زيادة إنتاج البول.',
                    'fr' => 'Aide à éliminer l\'excès de sel et d\'eau du corps en augmentant la production d\'urine.',
                ]
            ],
            [
                'name' => 'Laxative',
                'translations' => [
                    'ar' => 'ملين',
                    'fr' => 'Laxatif',
                ],
                'description' => 'Used to relieve constipation by stimulating bowel movements.',
                'description_translations' => [
                    'ar' => 'يستخدم لتخفيف الإمساك عن طريق تحفيز حركة الأمعاء.',
                    'fr' => 'Utilisé pour soulager la constipation en stimulant les mouvements intestinaux.',
                ]
            ],
            [
                'name' => 'Antiviral',
                'translations' => [
                    'ar' => 'مضاد للفيروسات',
                    'fr' => 'Antiviral',
                ],
                'description' => 'Used to treat viral infections by inhibiting the replication of viruses.',
                'description_translations' => [
                    'ar' => 'يستخدم لعلاج العدوى الفيروسية عن طريق تثبيط تكاثر الفيروسات.',
                    'fr' => 'Utilisé pour traiter les infections virales en inhibant la réplication des virus.',
                ]
            ],
            [
                'name' => 'Sedative',
                'translations' => [
                    'ar' => 'مهدئ',
                    'fr' => 'Sédatif',
                ],
                'description' => 'Used to calm the nervous system and induce relaxation.',
                'description_translations' => [
                    'ar' => 'يستخدم لتهدئة الجهاز العصبي وتحفيز الاسترخاء.',
                    'fr' => 'Utilisé pour calmer le système nerveux et induire la relaxation.',
                ]
            ],
            [
                'name' => 'Vitamins',
                'translations' => [
                    'ar' => 'فيتامينات',
                    'fr' => 'Vitamines',
                ],
                'description' => 'Used to supplement dietary intake of essential vitamins and minerals.',
                'description_translations' => [
                    'ar' => 'يستخدم كمكمل غذائي للفيتامينات والمعادن الأساسية.',
                    'fr' => 'Utilisé pour compléter l\'apport alimentaire en vitamines et minéraux essentiels.',
                ]
            ],
            [
                'name' => 'Antifungal',
                'translations' => [
                    'ar' => 'مضاد للفطريات',
                    'fr' => 'Antifongique',
                ],
                'description' => 'Used to treat fungal infections by killing or inhibiting the growth of fungi.',
                'description_translations' => [
                    'ar' => 'يستخدم لعلاج العدوى الفطرية عن طريق قتل الفطريات أو منع نموها.',
                    'fr' => 'Utilisé pour traiter les infections fongiques en tuant ou en inhibant la croissance des champignons.',
                ]
            ],
            [
                'name' => 'Bronchodilator',
                'translations' => [
                    'ar' => 'موسع الشعب الهوائية',
                    'fr' => 'Bronchodilatateur',
                ],
                'description' => 'Used to open the airways and ease breathing in conditions like asthma or COPD.',
                'description_translations' => [
                    'ar' => 'يستخدم لفتح المجاري الهوائية وتسهيل التنفس في حالات مثل الربو أو مرض الانسداد الرئوي المزمن.',
                    'fr' => 'Utilisé pour ouvrir les voies respiratoires et faciliter la respiration dans des conditions comme l\'asthme ou la BPCO.',
                ]
            ],
        ];


        foreach ($uses as $use) {
            $therapeuticUse = TherapeuticUse::create([
                'name' => $use['name'],
                'description' => $use['description'],
            ]);

            foreach ($use['translations'] as $locale => $translatedName) {
                Translation::create([
                    'translatable_id' => $therapeuticUse->id,
                    'translatable_type' => TherapeuticUse::class,
                    'locale' => $locale,
                    'field' => 'name',
                    'value' => $translatedName,
                ]);
            }

            foreach ($use['description_translations'] as $locale => $translatedDescription) {
                Translation::create([
                    'translatable_id' => $therapeuticUse->id,
                    'translatable_type' => TherapeuticUse::class,
                    'locale' => $locale,
                    'field' => 'description',
                    'value' => $translatedDescription,
                ]);
            }
        }
    }
}
