<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            // الربط مع الصنف المحدد في الفاتورة
            $table->foreignId('purchase_item_id')->constrained('purchase_items')->onDelete('cascade');
            // الربط مع الدواء لسهولة الاستعلام (اختياري ولكنه مفيد)
            $table->foreignId('drug_id')->constrained('drugs')->onDelete('cascade');
            // رقم الدفعة (بدون قيد فريد)
            $table->string('batch_number');
            // الكمية التي تم استلامها في هذه الدفعة
            $table->integer('quantity');
            // المخزون المتبقي من هذه الدفعة (يتم تحديثه عند البيع)
            $table->integer('stock');
            $table->date('expiry_date');
            // سعر بيع الوحدة للجمهور
            $table->decimal('selling_price', 8, 2);
            // حالة الدفعة (قيد الوصول,نشطة، منتهية الصلاحية، نفذت)
            $table->enum('status', ['pending', 'active', 'expired', 'sold_out'])->default('pending');            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
