<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_groups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('exam_group_exam', function (Blueprint $table): void {
            $table->foreignUuid('exam_group_id')->constrained('exam_groups')->cascadeOnDelete();
            $table->foreignUuid('exam_id')->constrained()->cascadeOnDelete();
            $table->timestampsTz();
            $table->primary(['exam_group_id', 'exam_id']);
        });

        Schema::create('exam_group_user', function (Blueprint $table): void {
            $table->foreignUuid('exam_group_id')->constrained('exam_groups')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestampsTz();
            $table->primary(['exam_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_group_user');
        Schema::dropIfExists('exam_group_exam');
        Schema::dropIfExists('exam_groups');
    }
};
