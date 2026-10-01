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
        Schema::table('stock_take_items', function (Blueprint $table) {
            $table->decimal('counted_quantity', 15, 4)->nullable()->change();
            $table->decimal('difference', 15, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_take_items', function (Blueprint $table) {
            $table->decimal('counted_quantity', 15, 4)->nullable(false)->change();
            $table->decimal('difference', 15, 4)->nullable(false)->change();
        });
    }
};
