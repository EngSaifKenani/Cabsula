<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('active_ingredient_therapeutic_use', function (Blueprint $table) {
            $table->id();
            $table->foreignId('active_ingredient_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('therapeutic_use_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->boolean('is_popular')->default(false)->comment('استخدام رئيسي؟');

            $table->unique(
                ['active_ingredient_id', 'therapeutic_use_id'],
                'ingredient_use_unique'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_ingredient_therapeutic_use');
    }
};
