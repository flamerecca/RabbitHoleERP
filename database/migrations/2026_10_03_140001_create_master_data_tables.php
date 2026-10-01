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
        Schema::create('unit_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('unit_categories');
            $table->string('name');
            $table->string('code');
            $table->decimal('ratio', 18, 8)->default(1);
            $table->boolean('is_reference')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'code']);
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('cost_method')->default('average');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('purchase_unit_id')->constrained('units');
            $table->string('sku');
            $table->string('name');
            $table->decimal('default_purchase_price', 15, 4)->nullable();
            $table->decimal('default_sales_price', 15, 4)->nullable();
            $table->decimal('reorder_point', 15, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'sku']);
        });

        Schema::create('product_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('supplier_product_code')->nullable();
            $table->string('supplier_product_name')->nullable();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('min_quantity', 15, 4)->default(0);
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->integer('sequence')->default(10);
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->decimal('exchange_rate_to_base', 18, 8)->default(1);
            $table->boolean('is_base')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'code']);
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 8, 4);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('address')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('manager_email')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'code']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('payment_terms')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'code']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('credit_limit', 15, 4)->nullable();
            $table->string('payment_terms')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('product_suppliers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('units');
        Schema::dropIfExists('unit_categories');
    }
};
