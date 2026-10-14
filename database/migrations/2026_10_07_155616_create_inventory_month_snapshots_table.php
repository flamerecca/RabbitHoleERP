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
        Schema::create('inventory_month_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('opening_quantity', 15, 4);
            $table->decimal('opening_amount', 15, 4);
            $table->decimal('in_quantity', 15, 4);
            $table->decimal('in_amount', 15, 4);
            $table->decimal('out_quantity', 15, 4);
            $table->decimal('out_amount', 15, 4);
            $table->decimal('closing_quantity', 15, 4);
            $table->decimal('closing_amount', 15, 4);
            $table->decimal('average_cost', 15, 4);
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->timestamps();

            $table->unique(['team_id', 'period', 'warehouse_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_month_snapshots');
    }
};
