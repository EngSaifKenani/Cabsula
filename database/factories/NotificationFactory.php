<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // سنقوم بتعريف مجموعة من أنواع الإشعارات المختلفة مع رسائل وروابط مناسبة
        $notificationTypes = [
            'success' => [
                'message' => 'تمت إضافة فاتورة شراء جديدة بنجاح برقم ' . fake()->numerify('INV-#####') . '.',
                'link' => '/purchase-invoices/' . fake()->numberBetween(1, 100),
            ],
            'warning' => [
                'message' => 'تنبيه: الدواء ' . fake()->words(2, true) . ' على وشك النفاد من المخزون. الكمية المتبقية: ' . fake()->numberBetween(1, 10) . '.',
                'link' => '/drugs/' . fake()->numberBetween(1, 200) . '/stock',
            ],
            'info' => [
                'message' => 'تم تحديث بيانات المورد: ' . fake()->company() . '.',
                'link' => '/suppliers/' . fake()->numberBetween(1, 50),
            ],
            'report' => [
                'message' => 'تقرير المبيعات الأسبوعي (' . now()->subWeek()->format('Y-m-d') . ') جاهز للعرض.',
                'link' => '/reports/weekly-sales/' . now()->subWeek()->year . '/' . now()->subWeek()->weekOfYear,
            ],
            'task' => [
                'message' => 'مهمة جديدة تم إسنادها إليك: جرد قسم الأدوية المسكنة.',
                'link' => '/tasks/' . fake()->numberBetween(1, 1000),
            ],
        ];

        // اختر نوع إشعار عشوائي من المصفوفة أعلاه
        $type = fake()->randomElement(array_keys($notificationTypes));
        $data = $notificationTypes[$type];

        return [
            'message' => $data['message'],
          //  'type' => $type,
            //'link' => $data['link'],
            //'read_at'=>null
        ];
    }
}
