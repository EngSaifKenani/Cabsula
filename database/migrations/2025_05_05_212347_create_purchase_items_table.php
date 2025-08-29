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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            // الربط مع الفاتورة الرئيسية التي ينتمي إليها هذا الصنف
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->onDelete('cascade');
            // الربط مع جدول الأدوية (تأكد من وجود جدول 'drugs')
            $table->foreignId('drug_id')->nullable()->constrained('drugs')->onDelete('set null');
            // إجمالي الكمية المطلوبة لهذا الصنف في الفاتورة
            $table->integer('quantity');
            // سعر شراء الوحدة (التكلفة)
            $table->decimal('unit_cost', 10, 2);
            // السعر الإجمالي لهذا الصنف (الكمية * سعر الوحدة)
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
