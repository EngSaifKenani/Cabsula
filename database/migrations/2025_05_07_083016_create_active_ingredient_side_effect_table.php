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
        Schema::create('active_ingredient_side_effect', function (Blueprint $table) {
            $table->id();
            $table->foreignId('active_ingredient_id')->constrained()->onDelete('cascade');
            $table->foreignId('side_effect_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_ingredient_side_effect');
    }
};
