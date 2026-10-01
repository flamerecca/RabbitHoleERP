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
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity_on_hand', 15, 4)->default(0);
            $table->decimal('quantity_reserved', 15, 4)->default(0);
            $table->decimal('average_cost', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_id')->constrained('products');
            $table->string('type');
            $table->decimal('quantity', 15, 4);
            $table->decimal('balance_after', 15, 4);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->nullableMorphs('reference');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['team_id', 'product_id', 'created_at']);
        });

        Schema::create('stock_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('stock_movement_id')->constrained('stock_movements');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('remaining_quantity', 15, 4);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['warehouse_id', 'product_id', 'remaining_quantity', 'id']);
        });

        Schema::create('stock_cost_layer_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_cost_layer_id')->constrained('stock_cost_layers');
            $table->foreignId('stock_movement_id')->constrained('stock_movements');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->string('transfer_no');
            $table->string('status');
            $table->date('transfer_date');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['team_id', 'transfer_no']);
        });

        Schema::create('warehouse_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 15, 4);
            $table->timestamps();
        });

        Schema::create('stock_takes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('take_no');
            $table->string('status');
            $table->date('taken_date');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['team_id', 'take_no']);
        });

        Schema::create('stock_take_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_take_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('system_quantity', 15, 4);
            $table->decimal('counted_quantity', 15, 4);
            $table->decimal('difference', 15, 4);
            $table->timestamps();
        });

        Schema::create('material_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('requisition_no');
            $table->date('requisition_date');
            $table->string('purpose');
            $table->foreignId('requested_by')->constrained('users');
            $table->string('status');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['team_id', 'requisition_no']);
        });

        Schema::create('reordering_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('min_quantity', 15, 4);
            $table->decimal('max_quantity', 15, 4);
            $table->decimal('multiple_quantity', 15, 4)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
        });

        Schema::create('material_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 15, 4);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_requisition_items');
        Schema::dropIfExists('reordering_rules');
        Schema::dropIfExists('material_requisitions');
        Schema::dropIfExists('stock_take_items');
        Schema::dropIfExists('stock_takes');
        Schema::dropIfExists('warehouse_transfer_items');
        Schema::dropIfExists('warehouse_transfers');
        Schema::dropIfExists('stock_cost_layer_consumptions');
        Schema::dropIfExists('stock_cost_layers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
    }
};
