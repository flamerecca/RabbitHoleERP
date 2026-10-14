<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_month_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->timestamps();

            $table->unique(['team_id', 'period']);
        });

        DB::statement(<<<'SQL'
            INSERT INTO inventory_month_closings (team_id, period, closed_by, closed_at, created_at, updated_at)
            SELECT team_id, period, MAX(closed_by), MAX(closed_at), now(), now()
            FROM inventory_month_snapshots
            GROUP BY team_id, period
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_month_closings');
    }
};
