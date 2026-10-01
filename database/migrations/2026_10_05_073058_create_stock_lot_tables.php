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
        Schema::table('products', function (Blueprint $table) {
            $table->string('tracking')->default('none')->after('is_active');
        });

        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('lot_no', 50);
            $table->timestamps();

            $table->unique(['team_id', 'product_id', 'lot_no']);
        });

        Schema::create('stock_lot_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('stock_lot_id')->constrained('stock_lots');
            $table->decimal('quantity_on_hand', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'stock_lot_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('stock_lot_id')->nullable()->after('product_id')->constrained('stock_lots');
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->string('lot_no', 50)->nullable()->after('unit_cost');
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            $table->foreignId('stock_lot_id')->nullable()->after('quantity')->constrained('stock_lots');
        });

        foreach (['purchase_return_items', 'sales_return_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('stock_lot_id')->nullable()->after('product_id')->constrained('stock_lots');
                $table->boolean('is_whole_lot')->default(false)->after('stock_lot_id');
            });
        }

        Schema::table('stock_take_items', function (Blueprint $table) {
            $table->foreignId('stock_lot_id')->nullable()->after('product_id')->constrained('stock_lots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['stock_take_items', 'sales_return_items', 'purchase_return_items', 'shipment_items', 'stock_movements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('stock_lot_id');
            });
        }

        foreach (['purchase_return_items', 'sales_return_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('is_whole_lot');
            });
        }

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn('lot_no');
        });

        Schema::dropIfExists('stock_lot_balances');
        Schema::dropIfExists('stock_lots');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tracking');
        });
    }
};
