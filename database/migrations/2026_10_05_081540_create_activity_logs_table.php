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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('c1')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('c2')->nullable()->constrained('users')->nullOnDelete();
            $table->string('c3');
            $table->string('c4');
            $table->unsignedBigInteger('c5');
            $table->string('c6');
            $table->unsignedBigInteger('c7');
            $table->string('c8')->nullable();
            $table->json('c9');
            $table->timestamp('c10')->nullable();

            $table->index(['c1', 'c6', 'c7']);
            $table->index(['c1', 'c10']);
            $table->index(['c1', 'c2']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
