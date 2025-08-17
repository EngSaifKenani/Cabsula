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
            $table->boolean('is_requires_prescription')->default(false);
            $table->string('image')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('barcode')->unique()->nullable(); // <-- هذا هو الحقل الجديد
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
