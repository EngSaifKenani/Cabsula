<?php

use App\Models\Form;
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
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->string('image')->nullable();
            $table->decimal('cost', 8, 2);
            $table->decimal('profit_amount')->default(0);
            $table->integer('stock')->default(0);
            $table->enum('status', ['active', 'expired'])->default('active');
            $table->boolean('is_requires_prescription')->default(false);
            $table->date('production_date')->nullable()->comment('تاريخ الإنتاج');
            $table->date('expiry_date')->comment('تاريخ انتهاء الصلاحية');
            $table->text('admin_notes')->nullable(); // ملاحظات إدارية
            $table->foreignId('form_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('manufacturer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('recommended_dosage_id')->nullable()->constrained('recommended_dosages')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
