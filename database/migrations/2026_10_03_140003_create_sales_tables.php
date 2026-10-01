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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('order_no');
            $table->string('status');
            $table->date('order_date');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['team_id', 'order_no']);
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->decimal('shipped_quantity', 15, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_id')->constrained('sales_orders');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('shipment_no');
            $table->date('shipped_date');
            $table->string('status');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['team_id', 'shipment_no']);
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 15, 4);
            $table->timestamps();
        });

        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('return_no');
            $table->date('return_date');
            $table->string('status');
            $table->string('reason');
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'return_no']);
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 15, 4);
            $table->string('disposition');
            $table->timestamps();
        });

        Schema::create('consignment_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_id')->unique()->constrained('shipments');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('hold_no');
            $table->date('held_from');
            $table->date('held_until');
            $table->string('status');
            $table->timestamp('picked_up_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['team_id', 'hold_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignment_holds');
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
