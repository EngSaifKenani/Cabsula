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
        Schema::create('active_ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('scientific_name')->unique();
            $table->text('description')->nullable();
            $table->string('cas_number')->nullable()->comment('CAS registry number');
            $table->string('unii_code')->nullable()->comment('Unique Ingredient Identifier');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_ingredients');
    }
};
