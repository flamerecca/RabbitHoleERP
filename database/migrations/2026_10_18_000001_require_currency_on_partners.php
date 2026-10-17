<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @var list<string>
     */
    private array $tables = ['suppliers', 'customers'];

    /**
     * Give the partners without a currency the base currency of their team, then require the currency.
     *
     * @throws RuntimeException when a team has such partners but no base currency.
     */
    public function up(): void
    {
        $this->ensureEveryTeamCanBeBackfilled();

        foreach ($this->tables as $table) {
            DB::table($table)
                ->whereNull('currency_id')
                ->update(['currency_id' => DB::raw("(select id from currencies where currencies.team_id = {$table}.team_id and currencies.is_base = true order by id limit 1)")]);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['currency_id']);
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('currency_id')->nullable(false)->change();
                $blueprint->foreign('currency_id')->references('id')->on('currencies')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['currency_id']);
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('currency_id')->nullable()->change();
                $blueprint->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
            });
        }
    }

    /**
     * Stop before changing anything when a team has partners without a currency and no base currency to give them.
     */
    private function ensureEveryTeamCanBeBackfilled(): void
    {
        $teamIds = collect($this->tables)
            ->flatMap(fn (string $table) => DB::table($table)->whereNull('currency_id')->distinct()->pluck('team_id'))
            ->unique()
            ->reject(fn ($teamId) => DB::table('currencies')->where('team_id', $teamId)->where('is_base', true)->exists());

        if ($teamIds->isNotEmpty()) {
            throw new RuntimeException('Teams without a base currency have suppliers or customers without a currency: '.$teamIds->sort()->implode(', ').'. Set a base currency or the currency of those partners first.');
        }
    }
};
