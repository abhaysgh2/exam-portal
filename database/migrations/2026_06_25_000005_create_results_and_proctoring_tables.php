<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->unique()->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignUuid('exam_id')->constrained();
            $table->foreignUuid('user_id')->constrained();
            $table->decimal('raw_score', 8, 2);
            $table->decimal('final_score', 8, 2);
            $table->integer('total_correct')->default(0);
            $table->integer('total_wrong')->default(0);
            $table->integer('total_unattempted')->default(0);
            $table->integer('total_questions')->default(0);
            $table->decimal('accuracy_pct', 5, 2)->nullable();
            $table->integer('time_taken_sec')->nullable();
            $table->decimal('percentile', 5, 2)->nullable();
            $table->integer('rank')->nullable();
            $table->string('grade', 5)->nullable();
            $table->boolean('is_pass')->nullable();
            $table->timestampTz('computed_at')->useCurrent();
            $table->index(['exam_id', 'final_score']);
        });

        Schema::create('proctoring_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->string('event_type', 50);
            $table->string('severity', 10);
            $table->json('details')->nullable();
            $table->text('screenshot_url')->nullable();
            $table->string('action', 20)->nullable();
            $table->text('action_note')->nullable();
            $table->timestampTz('logged_at')->useCurrent();
            $table->index('session_id');
            $table->index(['severity', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proctoring_logs');
        Schema::dropIfExists('results');
    }
};
