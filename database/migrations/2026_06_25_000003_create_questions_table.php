<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('question_bank_id')->nullable();
            $table->foreignUuid('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('exam_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('text');
            $table->text('image_url')->nullable();
            $table->text('explanation')->nullable();
            $table->string('difficulty', 10)->nullable();
            $table->string('topic')->nullable();
            $table->string('subtopic')->nullable();
            $table->decimal('marks', 5, 2)->default(1);
            $table->decimal('negative_marks', 5, 2)->default(0);
            $table->integer('order_index')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['exam_id', 'type']);
        });

        Schema::create('options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->text('text');
            $table->text('image_url')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->integer('order_index');
        });

        Schema::create('nat_answers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('correct_value', 12, 4);
            $table->decimal('tolerance', 12, 4)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nat_answers');
        Schema::dropIfExists('options');
        Schema::dropIfExists('questions');
    }
};
