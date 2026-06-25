<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamLifecycleTest extends TestCase
{
    use RefreshDatabase;

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
}
