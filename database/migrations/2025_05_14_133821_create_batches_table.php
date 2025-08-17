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
            $table->foreignId('purchase_item_id')->constrained('purchase_items')->onDelete('cascade');
            $table->foreignId('drug_id')->constrained('drugs')->onDelete('cascade');
            $table->string('batch_number');
            $table->integer('quantity');
            $table->integer('stock');
            $table->date('expiry_date');
            $table->decimal('unit_cost', 8, 2);
            $table->decimal('unit_price', 8, 2);

            $table->decimal('total', 8, 2);
            $table->enum('status', ['active', 'expired', 'sold_out'])->default('active');
            $table->timestamps();
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
