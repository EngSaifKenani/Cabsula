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
            $table->foreignId('drug_id')->nullable()->constrained('drugs')->onDelete('set null');
            $table->string('batch_number');
            $table->integer('quantity');
            $table->integer('stock');
            $table->date('expiry_date');
            $table->decimal('unit_cost', 8, 2);
            $table->decimal('unit_price', 8, 2);
            $table->boolean('is_expiry_notified')->default(false);
            $table->decimal('total', 8, 2);
            $table->enum('status', ['available', 'expired', 'sold_out'])->default('available');


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
