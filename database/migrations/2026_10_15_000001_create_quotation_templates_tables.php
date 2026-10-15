<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });

        Schema::create('quotation_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_template_id')->constrained('quotation_templates')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->timestamps();

            $table->unique(['quotation_template_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_template_items');
        Schema::dropIfExists('quotation_templates');
    }
};
