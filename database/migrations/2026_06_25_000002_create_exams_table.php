<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('duration_minutes');
            $table->decimal('total_marks', 8, 2);
            $table->decimal('pass_marks', 8, 2)->nullable();
            $table->timestampTz('start_time')->nullable();
            $table->timestampTz('end_time')->nullable();
            $table->integer('max_candidates')->default(10000);
            $table->string('status', 20)->default('draft')->index();
            $table->text('instructions')->nullable();
            $table->boolean('randomize_questions')->default(true);
            $table->boolean('randomize_options')->default(true);
            $table->boolean('allow_review')->default(true);
            $table->string('show_results_after', 20)->default('manual_release');
            $table->timestampTz('results_release_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('sections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order_index');
            $table->integer('time_limit_min')->nullable();
            $table->integer('total_questions')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
        Schema::dropIfExists('exams');
    }
};
