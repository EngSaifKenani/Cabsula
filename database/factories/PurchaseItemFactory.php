<?php

namespace Database\Factories;

use App\Models\Drug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // سنقوم بحساب الكمية وسعر التكلفة أولاً لنتمكن من حساب الإجمالي
        $quantity = fake()->numberBetween(10, 100);
        $unitCost = fake()->randomFloat(2, 1, 50); // سعر تكلفة عشوائي بين 1.00 و 50.00

        return [
            // اختر دواءً بشكل عشوائي من جدول الأدوية
            // هذا يتطلب وجود أدوية في قاعدة البيانات أولاً
            'drug_id' => Drug::inRandomOrder()->first()->id,

            // استخدم القيم التي حسبناها
            'quantity' => $quantity,
            'unit_cost' => $unitCost,

            // احسب الإجمالي بناءً على الكمية وسعر التكلفة
            'total' => $quantity * $unitCost,

            // لا تحتاج لوضع purchase_invoice_id هنا، لأن Laravel سيضيفه تلقائيًا
            // عند استخدام علاقة مثل $invoice->purchaseItems()->create()
        ];
    }
}
