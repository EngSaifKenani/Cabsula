<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str; // <-- لا تنس إضافة هذا السطر

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseInvoice>
 */
class PurchaseInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // سنقوم بتوليد بيانات مالية منطقية مبدئية
        // الـ Seeder سيقوم لاحقًا بتحديثها بالقيم النهائية بعد إضافة الأصناف
        $subtotal = fake()->randomFloat(2, 50, 1000);
        $discount = fake()->randomFloat(2, 0, $subtotal / 4); // خصم يصل إلى 25%
        $total = $subtotal - $discount;

        return [
            'invoice_number' => 'INV-' . fake()->unique()->numerify('#####'),
            'invoice_date' => fake()->dateTimeBetween('-1 year', 'now'),

            // توفير قيم أولية للحقول المالية
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,

            // توفير قيم أولية لحقول الدفع والحالة
            // الـ Seeder سيقوم بتحديث هذه القيم لاحقًا
            'status' => 'paid',

            'notes' => fake()->optional()->sentence(),

            // حقلي supplier_id و user_id سيتم توفيرهما من الـ Seeder عند استدعاء create()
        ];
    }
}
