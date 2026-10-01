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
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('prefix', 20);
            $table->string('date_format');
            $table->string('separator', 5)->default('');
            $table->unsignedTinyInteger('padding');
            $table->string('reset_period');
            $table->boolean('is_customized')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'document_type']);
        });

        Schema::create('document_sequence_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_sequence_id')->constrained()->cascadeOnDelete();
            $table->string('period_key');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['document_sequence_id', 'period_key']);
        });

        Schema::create('document_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('event');
            $table->string('condition_type');
            $table->decimal('threshold', 15, 4)->nullable();
            $table->json('partner_ids')->nullable();
            $table->string('action');
            $table->string('message');
            $table->boolean('is_active')->default(true);
            $table->integer('sequence')->default(10);
            $table->timestamps();

            $table->index(['team_id', 'document_type', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_rules');
        Schema::dropIfExists('document_sequence_counters');
        Schema::dropIfExists('document_sequences');
    }
};
