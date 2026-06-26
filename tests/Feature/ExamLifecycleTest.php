<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\NatAnswer;
use App\Models\Option;
use App\Models\Question;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_json(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertHeaderMissing('set-cookie');
    }

    public function test_login_returns_expiring_token_metadata(): void
    {
        User::factory()->create([
            'email' => 'student-login@example.com',
            'password' => 'password123',
            'role' => 'student',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'student-login@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at', 'user']);
    }

    public function test_examiner_can_renew_token_but_student_cannot(): void
    {
        $examiner = User::factory()->create(['role' => 'examiner']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($examiner)
            ->postJson('/api/v1/auth/renew')
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at', 'user']);

        $this->actingAs($student)
            ->postJson('/api/v1/auth/renew')
            ->assertForbidden()
            ->assertJsonPath('message', 'Student sessions cannot be renewed.');
    }

    public function test_demo_seeder_refreshes_demo_exam_live_window(): void
    {
        $examiner = User::factory()->create(['role' => 'examiner']);
        Exam::create([
            'title' => 'Demo Engineering Aptitude Exam',
            'created_by' => $examiner->id,
            'duration_minutes' => 60,
            'total_marks' => 10,
            'status' => 'live',
            'start_time' => now()->subHours(2),
            'end_time' => now()->subHour(),
        ]);

        $this->seed(DemoSeeder::class);

        $exam = Exam::where('title', 'Demo Engineering Aptitude Exam')->firstOrFail();
        $this->assertSame('live', $exam->status);
        $this->assertTrue($exam->start_time->lte(now()));
        $this->assertTrue($exam->end_time->gt(now()));
    }

    public function test_student_can_register_for_exam(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $examiner = User::factory()->create(['role' => 'examiner']);
        $exam = Exam::create([
            'title' => 'Sample Exam',
            'created_by' => $examiner->id,
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'scheduled',
        ]);

        $this->actingAs($student)
            ->postJson("/api/v1/exams/{$exam->id}/register")
            ->assertCreated();

        $this->assertDatabaseHas('registrations', ['exam_id' => $exam->id, 'user_id' => $student->id]);
    }

    public function test_unregistered_student_cannot_start_session(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'title' => 'Live Exam',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'live',
        ]);

        $this->actingAs($student)
            ->postJson('/api/v1/sessions/start', ['exam_id' => $exam->id])
            ->assertForbidden();
    }

    public function test_registered_student_can_start_live_session(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'title' => 'Live Exam',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'live',
        ]);
        Registration::create(['exam_id' => $exam->id, 'user_id' => $student->id]);

        $this->actingAs($student)
            ->postJson('/api/v1/sessions/start', ['exam_id' => $exam->id])
            ->assertCreated()
            ->assertJsonStructure(['session_id', 'questions', 'sections', 'time_remaining_sec', 'server_time']);
    }

    public function test_student_cannot_register_for_draft_exam(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'title' => 'Draft Registration Block',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'draft',
        ]);

        $this->actingAs($student)
            ->postJson("/api/v1/exams/{$exam->id}/register")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Registration is only open for scheduled or live exams.');
    }

    public function test_student_cannot_start_live_exam_before_start_time(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'title' => 'Future Live Exam',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'live',
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
        ]);
        Registration::create(['exam_id' => $exam->id, 'user_id' => $student->id]);

        $this->actingAs($student)
            ->postJson('/api/v1/sessions/start', ['exam_id' => $exam->id])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Exam has not started.');
    }

    public function test_examiner_cannot_enable_instant_results_without_answer_keys(): void
    {
        $examiner = User::factory()->create(['role' => 'examiner']);
        $exam = Exam::create([
            'title' => 'Missing Keys Exam',
            'created_by' => $examiner->id,
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'draft',
            'show_results_after' => 'manual_release',
        ]);
        Question::create([
            'exam_id' => $exam->id,
            'type' => 'mcq',
            'text' => 'Missing correct option',
            'marks' => 1,
        ]);

        $this->actingAs($examiner)
            ->patchJson("/api/v1/exams/{$exam->id}/instant-results", ['enabled' => true])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'please first upload the answers']);
    }

    public function test_examiner_can_enable_instant_results_when_answer_keys_exist(): void
    {
        $examiner = User::factory()->create(['role' => 'examiner']);
        $exam = Exam::create([
            'title' => 'Ready Keys Exam',
            'created_by' => $examiner->id,
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'draft',
            'show_results_after' => 'manual_release',
        ]);
        $question = Question::create([
            'exam_id' => $exam->id,
            'type' => 'nat',
            'text' => 'Square root of 81',
            'marks' => 10,
        ]);
        NatAnswer::create(['question_id' => $question->id, 'correct_value' => 9, 'tolerance' => 0]);

        $this->actingAs($examiner)
            ->patchJson("/api/v1/exams/{$exam->id}/instant-results", ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('instant_results_enabled', true);

        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'show_results_after' => 'submit']);
    }

    public function test_examiner_can_create_and_publish_basic_mcq_test(): void
    {
        $examiner = User::factory()->create(['role' => 'examiner']);

        $response = $this->actingAs($examiner)
            ->postJson('/api/v1/exams', [
                'title' => 'UI Created Test',
                'duration_minutes' => 30,
                'total_marks' => 5,
                'pass_marks' => 2,
                'status' => 'draft',
                'show_results_after' => 'submit',
                'starter_question' => [
                    'text' => 'What is 2 + 3?',
                    'marks' => 5,
                    'negative_marks' => 0,
                    'options' => [
                        ['text' => '4', 'is_correct' => false],
                        ['text' => '5', 'is_correct' => true],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('title', 'UI Created Test');

        $examId = $response->json('id');

        $this->actingAs($examiner)
            ->postJson("/api/v1/exams/{$examId}/publish")
            ->assertOk()
            ->assertJsonPath('status', 'scheduled');

        $this->assertDatabaseHas('questions', ['exam_id' => $examId, 'text' => 'What is 2 + 3?']);
    }

    public function test_examiner_can_only_edit_tests_they_created_while_admin_can_edit_any_test(): void
    {
        $owner = User::factory()->create(['role' => 'examiner']);
        $otherExaminer = User::factory()->create(['role' => 'examiner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $exam = Exam::create([
            'title' => 'Owned Draft',
            'created_by' => $owner->id,
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'draft',
        ]);

        $this->actingAs($otherExaminer)
            ->putJson("/api/v1/exams/{$exam->id}", ['title' => 'Blocked Edit'])
            ->assertForbidden();

        $this->actingAs($owner)
            ->putJson("/api/v1/exams/{$exam->id}", ['title' => 'Owner Edit'])
            ->assertOk()
            ->assertJsonPath('title', 'Owner Edit');

        $this->actingAs($admin)
            ->putJson("/api/v1/exams/{$exam->id}", ['title' => 'Admin Edit'])
            ->assertOk()
            ->assertJsonPath('title', 'Admin Edit');
    }

    public function test_admin_can_delete_any_non_live_test(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $examiner = User::factory()->create(['role' => 'examiner']);
        $exam = Exam::create([
            'title' => 'Delete Candidate',
            'created_by' => $examiner->id,
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/exams/{$exam->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    public function test_group_assignment_registers_students_for_group_tests(): void
    {
        $examiner = User::factory()->create(['role' => 'examiner']);
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'title' => 'Group Test',
            'created_by' => $examiner->id,
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'scheduled',
        ]);

        $groupResponse = $this->actingAs($examiner)
            ->postJson('/api/v1/exam-groups', ['name' => 'Batch A'])
            ->assertCreated()
            ->assertJsonPath('name', 'Batch A');

        $groupId = $groupResponse->json('id');

        $this->actingAs($examiner)
            ->postJson("/api/v1/exam-groups/{$groupId}/exams", ['exam_id' => $exam->id])
            ->assertOk();

        $this->actingAs($examiner)
            ->postJson("/api/v1/exam-groups/{$groupId}/students", ['user_id' => $student->id])
            ->assertOk()
            ->assertJsonPath('students.0.email', $student->email);

        $this->assertDatabaseHas('exam_group_exam', ['exam_group_id' => $groupId, 'exam_id' => $exam->id]);
        $this->assertDatabaseHas('exam_group_user', ['exam_group_id' => $groupId, 'user_id' => $student->id]);
        $this->assertDatabaseHas('registrations', ['exam_id' => $exam->id, 'user_id' => $student->id]);
    }

    public function test_submit_hides_result_when_instant_results_are_disabled(): void
    {
        [$student, $session, $question, $option] = $this->sessionFixture('manual_release');

        $this->actingAs($student)
            ->patchJson("/api/v1/sessions/{$session->id}/answer", [
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ])
            ->assertOk();

        $this->actingAs($student)
            ->postJson("/api/v1/sessions/{$session->id}/submit")
            ->assertOk()
            ->assertJsonPath('result_visible', false)
            ->assertJsonPath('result', null)
            ->assertJsonPath('message', 'Your test is submitted and will be evaluated. You can exit the test.');
    }

    public function test_submit_returns_result_when_instant_results_are_enabled(): void
    {
        [$student, $session, $question, $option] = $this->sessionFixture('submit');

        $this->actingAs($student)
            ->patchJson("/api/v1/sessions/{$session->id}/answer", [
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ])
            ->assertOk();

        $this->actingAs($student)
            ->postJson("/api/v1/sessions/{$session->id}/submit")
            ->assertOk()
            ->assertJsonPath('result_visible', true)
            ->assertJsonPath('result.final_score', '10.00')
            ->assertJsonPath('message', 'Your result is ready.');
    }

    public function test_answer_can_be_saved_during_one_minute_timer_grace(): void
    {
        [$student, $session, $question, $option] = $this->sessionFixture('manual_release');
        $session->forceFill(['started_at' => now()->subSeconds((30 * 60) + 30)])->save();

        $this->actingAs($student)
            ->patchJson("/api/v1/sessions/{$session->id}/answer", [
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ])
            ->assertOk();
    }

    public function test_answer_is_rejected_after_one_minute_timer_grace(): void
    {
        [$student, $session, $question, $option] = $this->sessionFixture('manual_release');
        $session->forceFill(['started_at' => now()->subSeconds((30 * 60) + 61)])->save();

        $this->actingAs($student)
            ->patchJson("/api/v1/sessions/{$session->id}/answer", [
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Session time has expired.');
    }

    private function sessionFixture(string $showResultsAfter): array
    {
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'title' => "Submit {$showResultsAfter}",
            'duration_minutes' => 30,
            'total_marks' => 10,
            'status' => 'live',
            'show_results_after' => $showResultsAfter,
        ]);
        Registration::create(['exam_id' => $exam->id, 'user_id' => $student->id]);
        $question = Question::create([
            'exam_id' => $exam->id,
            'type' => 'mcq',
            'text' => 'What is 2 + 2?',
            'marks' => 10,
        ]);
        $option = Option::create(['question_id' => $question->id, 'text' => '4', 'is_correct' => true, 'order_index' => 1]);
        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return [$student, $session, $question, $option];
    }
}
