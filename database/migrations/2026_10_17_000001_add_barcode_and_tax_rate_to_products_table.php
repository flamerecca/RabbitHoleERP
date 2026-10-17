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
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode', 50)->nullable()->after('sku');
            $table->foreignId('tax_rate_id')->nullable()->after('default_sales_price')->constrained('tax_rates');

            $table->unique(['team_id', 'barcode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'barcode']);
            $table->dropConstrainedForeignId('tax_rate_id');
            $table->dropColumn('barcode');
        });
    }
};
