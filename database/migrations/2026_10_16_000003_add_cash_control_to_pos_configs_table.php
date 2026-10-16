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
        Schema::table('pos_configs', function (Blueprint $table) {
            $table->boolean('cash_control')->default(true)->after('receipt_footer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_configs', function (Blueprint $table) {
            $table->dropColumn('cash_control');
        });
    }
};
