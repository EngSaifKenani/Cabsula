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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_person')->nullable(); // اسم المسؤول
            $table->text('note')->nullable();
            $table->decimal('account_balance', 12, 2)->default(0);
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();


            $table->string('tax_number')->nullable(); // الرقم الضريبي
            $table->string('commercial_register')->nullable(); // السجل التجاري
            $table->index('name');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
