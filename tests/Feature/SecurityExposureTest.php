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

    public function test_student_exam_details_do_not_expose_question_content_before_session_start(): void
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
            ->assertJsonMissingPath('questions');
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

    public function test_examiner_cannot_save_or_submit_student_session_answers(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $examiner = User::factory()->create(['role' => 'examiner']);
        $exam = Exam::create([
            'title' => 'Protected Session Exam',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'live',
        ]);
        $question = Question::create([
            'exam_id' => $exam->id,
            'type' => 'mcq',
            'text' => 'Private answer',
            'marks' => 1,
        ]);
        $option = Option::create(['question_id' => $question->id, 'text' => 'A', 'is_correct' => true, 'order_index' => 1]);
        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $this->actingAs($examiner)
            ->patchJson("/api/v1/sessions/{$session->id}/answer", [
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ])
            ->assertForbidden();

        $this->actingAs($examiner)
            ->postJson("/api/v1/sessions/{$session->id}/submit")
            ->assertForbidden();
    }

    public function test_expired_session_cannot_accept_answers(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'title' => 'Expired Session Exam',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'live',
        ]);
        $question = Question::create([
            'exam_id' => $exam->id,
            'type' => 'mcq',
            'text' => 'Too late',
            'marks' => 1,
        ]);
        $option = Option::create(['question_id' => $question->id, 'text' => 'A', 'is_correct' => true, 'order_index' => 1]);
        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(31),
            'status' => 'in_progress',
        ]);

        $this->actingAs($student)
            ->patchJson("/api/v1/sessions/{$session->id}/answer", [
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ])
            ->assertStatus(409);
    }
}
