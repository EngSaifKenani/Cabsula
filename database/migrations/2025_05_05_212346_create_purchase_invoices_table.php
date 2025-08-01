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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            // رقم الفاتورة الخاص بالمورد، ويفضل أن يكون فريداً
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            // الربط مع جدول الموردين (تأكد من وجود جدول 'suppliers')
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            // الإجمالي الفرعي (مجموع أسعار الأصناف قبل الضريبة والخصم)
            $table->decimal('subtotal', 12, 2);
            // قيمة الخصم على الفاتورة
            $table->decimal('discount', 12, 2)->default(0);
            // المبلغ الإجمالي النهائي بعد الخصم
            $table->decimal('total', 12, 2);
            // حالة الفاتورة (مسودة، مكتملة، ملغاة)
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            // الربط مع المستخدم الذي قام بإدخال الفاتورة (تأكد من وجود جدول 'users')
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes(); // لإضافة الحذف الناعم
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
