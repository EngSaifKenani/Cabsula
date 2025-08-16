<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id(); // المعرّف الرئيسي لعملية الجرد
            $table->timestamp('count_date'); // تاريخ ووقت الجرد

            // نفترض أن لديك جدول 'users' لتحديد من قام بالجرد
            $table->foreignId('admin_id')->constrained('users');

            $table->text('notes')->nullable(); // ملاحظات عامة
            $table->timestamps(); // تضيف حقول created_at و updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_counts');
    }
};
