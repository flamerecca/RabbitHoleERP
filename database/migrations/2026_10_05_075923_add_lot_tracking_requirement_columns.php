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
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('require_lot_tracking')->default(false)->after('is_personal');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('lot_tracking')->nullable()->after('cost_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('lot_tracking');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('require_lot_tracking');
        });
    }
};
