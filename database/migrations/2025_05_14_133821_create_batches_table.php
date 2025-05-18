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
            $table->foreignId('drug_id')->constrained()->onDelete('cascade');
            $table->string('batch_number')->unique()->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('stock')->default(0);
            $table->integer('sold')->default(0);
            $table->date('production_date');
            $table->date('expiry_date');
            $table->decimal('cost', 8, 2);
            $table->decimal('price', 8, 2);
            $table->enum('status', ['active', 'expired'])->default('active');
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
