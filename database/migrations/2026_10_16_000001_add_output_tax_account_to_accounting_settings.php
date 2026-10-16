<?php

use App\Enums\AccountType;
use App\Models\AccountingSetting;
use App\Models\ChartOfAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Add the output tax account to the accounting settings and fill it for every team.
     *
     * A team that already has an account with code 2200 keeps it, so the migration can run again safely.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('accounting_settings', 'output_tax_account_id')) {
            Schema::table('accounting_settings', function (Blueprint $table) {
                $table->foreignId('output_tax_account_id')->nullable()->after('inventory_adjustment_account_id')->constrained('chart_of_accounts');
            });
        }

        AccountingSetting::query()->whereNull('output_tax_account_id')->each(function (AccountingSetting $settings): void {
            $account = ChartOfAccount::query()->firstOrCreate(
                ['team_id' => $settings->team_id, 'code' => '2200'],
                ['name' => '銷項稅額', 'account_type' => AccountType::Liability, 'is_active' => true],
            );

            $settings->update(['output_tax_account_id' => $account->id]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('output_tax_account_id');
        });
    }
};
