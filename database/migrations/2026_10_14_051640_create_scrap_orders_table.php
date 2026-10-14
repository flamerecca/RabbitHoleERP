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
        Schema::create('scrap_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('scrap_no');
            $table->string('status');
            $table->text('reason')->nullable();
            $table->timestamp('scrapped_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['team_id', 'scrap_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrap_orders');
    }
};
