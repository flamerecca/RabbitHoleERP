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
        Schema::table('stock_lots', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('lot_no');
            $table->index(['team_id', 'expiry_date']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('default_sales_price');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedSmallInteger('expiry_warning_days')->default(30);
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->date('lot_expiry_date')->nullable()->after('lot_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn('lot_expiry_date');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('expiry_warning_days');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });

        Schema::table('stock_lots', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'expiry_date']);
            $table->dropColumn('expiry_date');
        });
    }
};
