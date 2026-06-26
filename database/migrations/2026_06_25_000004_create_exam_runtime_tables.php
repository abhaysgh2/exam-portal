<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('registered_at')->useCurrent();
            $table->string('seat_number', 50)->nullable();
            $table->string('status', 20)->default('registered');
            $table->unique(['exam_id', 'user_id']);
        });

        Schema::create('exam_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained();
            $table->foreignUuid('user_id')->constrained();
            $table->timestampTz('started_at')->useCurrent();
            $table->timestampTz('submitted_at')->nullable();
            $table->integer('time_remaining_sec')->nullable();
            $table->string('status', 20)->default('in_progress');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignUuid('current_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->json('question_order')->nullable();
            $table->timestampsTz();
            $table->unique(['exam_id', 'user_id']);
            $table->index(['exam_id', 'status']);
        });

        Schema::create('answers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained();
            $table->foreignUuid('selected_option_id')->nullable()->constrained('options')->nullOnDelete();
            $table->json('selected_option_ids')->nullable();
            $table->decimal('nat_value', 12, 4)->nullable();
            $table->text('descriptive_text')->nullable();
            $table->decimal('manual_score', 8, 2)->nullable();
            $table->text('manual_feedback')->nullable();
            $table->boolean('is_marked_review')->default(false);
            $table->boolean('visited')->default(false);
            $table->integer('time_spent_sec')->default(0);
            $table->timestampTz('answered_at')->nullable();
            $table->timestampsTz();
            $table->unique(['session_id', 'question_id']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
        Schema::dropIfExists('exam_sessions');
        Schema::dropIfExists('registrations');
    }
};
