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
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_session_id')->constrained('pos_sessions');
            $table->string('order_no');
            $table->string('status');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->decimal('amount_total', 15, 4)->default(0);
            $table->decimal('amount_tax', 15, 4)->default(0);
            $table->decimal('amount_paid', 15, 4)->default(0);
            $table->decimal('amount_change', 15, 4)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'order_no']);
            $table->index(['pos_session_id', 'status']);
        });

        Schema::create('pos_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 15, 4);
            $table->decimal('list_price', 15, 4)->nullable();
            $table->decimal('unit_price', 15, 4);
            $table->decimal('product_discount_rate', 8, 4)->nullable();
            $table->decimal('discount_rate', 8, 4)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates');
            $table->decimal('tax_rate', 7, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            $table->decimal('line_tax', 15, 4);
            $table->timestamps();
        });

        Schema::create('pos_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
            $table->foreignId('pos_payment_method_id')->constrained('pos_payment_methods');
            $table->decimal('amount', 15, 4);
            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_payments');
        Schema::dropIfExists('pos_order_lines');
        Schema::dropIfExists('pos_orders');
    }
};
