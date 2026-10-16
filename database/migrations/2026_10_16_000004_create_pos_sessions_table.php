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
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_config_id')->constrained('pos_configs');
            $table->string('session_no');
            $table->string('status');
            $table->foreignId('opened_by')->constrained('users');
            $table->timestamp('opened_at');
            $table->decimal('opening_cash', 15, 4)->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('closing_cash_counted', 15, 4)->nullable();
            $table->decimal('closing_cash_expected', 15, 4)->nullable();
            $table->decimal('cash_difference', 15, 4)->nullable();
            $table->text('closing_note')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'session_no']);
            $table->index(['pos_config_id', 'status']);
        });

        DB::statement("CREATE UNIQUE INDEX pos_sessions_one_open_per_config ON pos_sessions (pos_config_id) WHERE status = 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
