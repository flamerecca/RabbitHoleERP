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
        Schema::create('scrap_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrap_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 15, 4);
            $table->timestamps();

            $table->unique(['scrap_order_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrap_order_items');
    }
};
