<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Option;
use App\Models\Question;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityExposureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_cannot_self_assign_admin_role(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'email' => 'candidate@example.com',
            'password' => 'password123',
            'full_name' => 'Candidate User',
            'role' => 'admin',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('users', ['email' => 'candidate@example.com', 'role' => 'admin']);
    }

    public function test_exam_details_do_not_expose_correct_options(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'title' => 'Security Exam',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'live',
        ]);
        $question = Question::create([
            'exam_id' => $exam->id,
            'type' => 'mcq',
            'text' => 'Hidden answer?',
            'marks' => 1,
        ]);
        Option::create(['question_id' => $question->id, 'text' => 'Correct', 'is_correct' => true, 'order_index' => 1]);

        $this->actingAs($student)
            ->getJson("/api/v1/exams/{$exam->id}")
            ->assertOk()
            ->assertJsonMissingPath('questions.0.options.0.is_correct');
    }

    public function test_answer_cannot_reference_question_from_another_exam(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'title' => 'Allowed Exam',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'live',
        ]);
        Registration::create(['exam_id' => $exam->id, 'user_id' => $student->id]);
        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $otherExam = Exam::create([
            'title' => 'Other Exam',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'live',
        ]);
        $otherQuestion = Question::create([
            'exam_id' => $otherExam->id,
            'type' => 'mcq',
            'text' => 'Not yours',
            'marks' => 1,
        ]);

        $this->actingAs($student)
            ->patchJson("/api/v1/sessions/{$session->id}/answer", ['question_id' => $otherQuestion->id])
            ->assertNotFound();
    }
}
