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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // من أنشأ الفاتورة
            $table->decimal('total_cost', 10, 2)->default(0); // مجموع التكلفة
            $table->decimal('total_price', 10, 2)->default(0); // مجموع البيع
            $table->decimal('total_profit', 10, 2)->default(0); // مجموع الربح
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
