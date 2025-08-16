<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_count_details', function (Blueprint $table) {
            $table->id(); // المعرّف الرئيسي للتفاصيل

            // الربط مع جدول الجرد الرئيسي
            $table->foreignId('inventory_count_id')->constrained('inventory_counts')->onDelete('cascade');

            // نفترض أن لديك جدول 'medicines' و 'batches'
            $table->foreignId('drug_id')->constrained('drugs');
            $table->foreignId('batch_id')->constrained('batches');

            $table->integer('system_quantity'); // الكمية في النظام
            $table->integer('counted_quantity'); // الكمية المعدودة فعلياً
            $table->text('reason')->nullable(); // سبب الفرق (إن وجد)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_count_details');
    }
};
