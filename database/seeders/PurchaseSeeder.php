<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Drug;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // احصل على المستخدم الأول لتعيينه كمنشئ للفواتير، أو أنشئ واحدًا إذا لم يكن موجودًا
        $user = User::first() ?? User::factory()->create();

        // أنشئ 20 دواءً وهميًا
        $drugs = Drug::factory()->count(20)->create();

        // أنشئ 10 موردين، ولكل مورد...
        Supplier::factory()->count(10)->create()->each(function ($supplier) use ($user, $drugs) {

            // ...أنشئ عددًا عشوائيًا من الفواتير (من 2 إلى 5 فواتير لكل مورد)
            PurchaseInvoice::factory()->count(rand(2, 5))->create([
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
            ])->each(function ($invoice) use ($drugs) {

                // لكل فاتورة، أنشئ مجموعة عشوائية من أصناف الفاتورة (من 3 إلى 8 أصناف)
                $purchaseItems = PurchaseItem::factory()->count(rand(3, 8))->make(); // make() ينشئها في الذاكرة فقط

                $subtotal = 0;

                // لكل صنف فاتورة...
                foreach ($purchaseItems as $item) {
                    // اختر دواءً عشوائيًا من مجموعة الأدوية
                    $randomDrug = $drugs->random();
                    $item->drug_id = $randomDrug->id;
                    $item->unit_cost = rand(10, 100) / 10; // سعر تكلفة عشوائي بين 1.0 و 10.0
                    $item->quantity = rand(10, 100);
                    $item->total = $item->unit_cost * $item->quantity;

                    $subtotal += $item->total;

                    // الآن احفظ الصنف مرتبطًا بالفاتورة
                    $invoice->purchaseItems()->save($item);

                    // --- إنشاء الدفعات ---
                    $remainingQuantity = $item->quantity;
                    while ($remainingQuantity > 0) {
                        $batchQuantity = rand(1, $remainingQuantity);

                        Batch::factory()->create([
                            'purchase_item_id' => $item->id,
                            'drug_id' => $item->drug_id,
                            'quantity' => $batchQuantity,
                            'stock' => $batchQuantity,
                            'unit_cost' => $item->unit_cost, // انسخ سعر التكلفة من الصنف الأب
                            'unit_price' => $item->unit_cost * (1 + rand(20, 50) / 100), // سعر البيع = التكلفة + ربح من 20% إلى 50%
                            'expiry_date' => now()->addYears(rand(1, 3)),
                        ]);

                        $remainingQuantity -= $batchQuantity;
                    }
                }

                // الآن بعد حساب الإجمالي الفرعي، قم بتحديث الفاتورة بالقيم المالية الصحيحة
                $discount = $subtotal > 100 ? rand(5, 20) : 0; // خصم عشوائي إذا كانت الفاتورة كبيرة
                $total = $subtotal - $discount;

                // تحديد حالة الدفع بشكل عشوائي
                $amountPaid = 0;
                $status = 'unpaid';
                $chance = rand(1, 10);
                if ($chance > 7) { // 30% fully paid
                    $amountPaid = $total;
                    $status = 'paid';
                } elseif ($chance > 4) { // 30% partially paid
                    $amountPaid = $total / 2;
                    $status = 'partially_paid';
                }

                $invoice->update([
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'status' => 'paid',
                ]);
            });
        });
    }
}
