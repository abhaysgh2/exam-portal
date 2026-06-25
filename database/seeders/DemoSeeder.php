<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\NatAnswer;
use App\Models\Option;
use App\Models\Question;
use App\Models\Registration;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(app()->environment('production'), 403, 'DemoSeeder must not run in production.');

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['password' => 'password123', 'full_name' => 'Admin User', 'role' => 'admin'],
        );

        $examiner = User::firstOrCreate(
            ['email' => 'examiner@example.com'],
            ['password' => 'password123', 'full_name' => 'Examiner User', 'role' => 'examiner'],
        );

        $student = User::firstOrCreate(
            ['email' => 'student@example.com'],
            ['password' => 'password123', 'full_name' => 'Student User', 'role' => 'student', 'enrollment_no' => 'ENR-1001'],
        );

        $exam = Exam::firstOrCreate(
            ['title' => 'Demo Engineering Aptitude Exam'],
            [
                'created_by' => $examiner->id,
                'category' => 'engineering',
                'description' => 'Seed exam for local API testing.',
                'duration_minutes' => 60,
                'total_marks' => 10,
                'pass_marks' => 4,
                'start_time' => now()->subMinute(),
                'end_time' => now()->addHour(),
                'status' => 'live',
                'instructions' => 'Answer all questions. Negative marking applies.',
            ],
        );

        $section = Section::firstOrCreate(
            ['exam_id' => $exam->id, 'title' => 'General Aptitude'],
            ['order_index' => 1, 'time_limit_min' => null, 'total_questions' => 2],
        );

        $mcq = Question::firstOrCreate(
            ['exam_id' => $exam->id, 'text' => 'What is 2 + 2?'],
            [
                'section_id' => $section->id,
                'type' => 'mcq',
                'difficulty' => 'easy',
                'topic' => 'Math',
                'marks' => 4,
                'negative_marks' => 1,
                'order_index' => 1,
                'created_by' => $examiner->id,
            ],
        );

        Option::firstOrCreate(['question_id' => $mcq->id, 'text' => '3'], ['is_correct' => false, 'order_index' => 1]);
        Option::firstOrCreate(['question_id' => $mcq->id, 'text' => '4'], ['is_correct' => true, 'order_index' => 2]);

        $nat = Question::firstOrCreate(
            ['exam_id' => $exam->id, 'text' => 'Enter the square root of 81.'],
            [
                'section_id' => $section->id,
                'type' => 'nat',
                'difficulty' => 'easy',
                'topic' => 'Math',
                'marks' => 6,
                'negative_marks' => 0,
                'order_index' => 2,
                'created_by' => $examiner->id,
            ],
        );

        NatAnswer::firstOrCreate(['question_id' => $nat->id], ['correct_value' => 9, 'tolerance' => 0]);

        Registration::firstOrCreate(
            ['exam_id' => $exam->id, 'user_id' => $student->id],
            ['status' => 'registered', 'registered_at' => now()],
        );

        $this->command?->info('Demo users seeded for local development only.');
    }
}
