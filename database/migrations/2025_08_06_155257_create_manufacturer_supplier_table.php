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
        Schema::create('manufacturer_supplier', function (Blueprint $table) {
            // المفتاح الأجنبي لجدول الشركات المصنّعة
            // onDelete('cascade') تعني أنه إذا تم حذف شركة مصنعة، سيتم حذف كل ارتباطاتها بالموردين
            $table->foreignId('manufacturer_id')->constrained()->onDelete('cascade');

            // المفتاح الأجنبي لجدول الموردين
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');

            // (موصى به بشدة)
            // إنشاء مفتاح أساسي مركب (Composite Primary Key)
            // هذا يمنع تسجيل نفس العلاقة (نفس المورد مع نفس الشركة المصنعة) أكثر من مرة
            $table->primary(['manufacturer_id', 'supplier_id']);});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturer_supplier');
    }
};
