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
        Schema::create('drug_ingredients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('drug_id')
                ->constrained('drugs')
                ->cascadeOnDelete();

            $table->foreignId('active_ingredient_id')
                ->constrained('active_ingredients')
                ->cascadeOnDelete();

            $table->decimal('concentration', 8, 2);
            $table->enum('unit', ['mg', 'g', 'ml', 'mcg', 'IU'])->default('mg');
            $table->timestamps();
            $table->unique([
                'drug_id',
                'active_ingredient_id',
            ], 'drug_ingredient_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_ingredients');
    }
};
