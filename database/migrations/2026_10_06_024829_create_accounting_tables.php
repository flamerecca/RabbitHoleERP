<?php

use App\Actions\Accounting\SeedDefaultAccounting;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('account_type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'code']);
        });

        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('type');
            $table->foreignId('default_account_id')->nullable()->constrained('chart_of_accounts');
            $table->timestamps();

            $table->unique(['team_id', 'code']);
        });

        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_account_id')->constrained('chart_of_accounts');
            $table->foreignId('receivable_account_id')->constrained('chart_of_accounts');
            $table->foreignId('payable_account_id')->constrained('chart_of_accounts');
            $table->foreignId('sales_revenue_account_id')->constrained('chart_of_accounts');
            $table->foreignId('cost_of_goods_sold_account_id')->constrained('chart_of_accounts');
            $table->foreignId('research_expense_account_id')->constrained('chart_of_accounts');
            $table->foreignId('inventory_adjustment_account_id')->constrained('chart_of_accounts');
            $table->foreignId('sales_journal_id')->constrained('journals');
            $table->foreignId('purchase_journal_id')->constrained('journals');
            $table->foreignId('general_journal_id')->constrained('journals');
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained('journals');
            $table->string('entry_no');
            $table->date('entry_date');
            $table->nullableMorphs('reference');
            $table->string('status');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['team_id', 'entry_no']);
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->string('partner_type')->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->decimal('debit', 15, 4)->default(0);
            $table->decimal('credit', 15, 4)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 18, 8)->nullable();
            $table->decimal('amount_currency', 15, 4)->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount_residual', 15, 4)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['partner_type', 'partner_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('payment_no');
            $table->string('payment_type');
            $table->string('partner_type');
            $table->unsignedBigInteger('partner_id');
            $table->foreignId('journal_id')->constrained('journals');
            $table->decimal('amount', 15, 4);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->date('payment_date');
            $table->string('status');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['team_id', 'payment_no']);
            $table->index(['partner_type', 'partner_id']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_line_id')->constrained('journal_entry_lines');
            $table->decimal('amount', 15, 4);
            $table->timestamps();
        });

        $seedDefaultAccounting = app(SeedDefaultAccounting::class);
        Team::withTrashed()->each(fn (Team $team) => $seedDefaultAccounting->handle($team));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_settings');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('chart_of_accounts');
    }
};
