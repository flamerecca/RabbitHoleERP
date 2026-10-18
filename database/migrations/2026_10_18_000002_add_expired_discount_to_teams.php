<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('sell_expired_at_discount')->default(false);
            $table->decimal('expired_discount_rate', 5, 2)->default(50);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['sell_expired_at_discount', 'expired_discount_rate']);
        });
    }
};
