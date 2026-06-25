<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Services\ExamTimerService;
use App\Services\GradingService;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(private ExamTimerService $timer, private GradingService $grading)
    {
    }

    public function start(Request $request)
    {
        $data = $request->validate(['exam_id' => ['required', 'uuid', 'exists:exams,id']]);
        $exam = Exam::with('questions.options', 'sections')->findOrFail($data['exam_id']);

        abort_unless($exam->status === 'live', 400, 'Exam is not live.');
        abort_unless($exam->registrations()->where('user_id', $request->user()->id)->exists(), 403, 'You are not registered for this exam.');

        $questions = $exam->questions;
        $order = $exam->randomize_questions ? $questions->pluck('id')->shuffle()->values()->all() : $questions->pluck('id')->values()->all();

        $session = ExamSession::firstOrCreate(
            ['exam_id' => $exam->id, 'user_id' => $request->user()->id],
            [
                'started_at' => now(),
                'time_remaining_sec' => $exam->duration_minutes * 60,
                'status' => 'in_progress',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'question_order' => $order,
            ],
        );

        return response()->json([
            'session_id' => $session->id,
            'questions' => $questions->values(),
            'sections' => $exam->sections,
            'time_remaining_sec' => $this->timer->remainingSeconds($session),
            'server_time' => now(),
        ], 201);
    }

    public function show(Request $request, ExamSession $session)
    {
        $this->authorizeSession($request, $session);

        return response()->json([
            'session' => $session->load('exam', 'answers'),
            'time_remaining_sec' => $this->timer->remainingSeconds($session),
        ]);
    }

    public function answer(Request $request, ExamSession $session)
    {
        $this->authorizeSession($request, $session);
        abort_if($session->submitted_at, 409, 'Session has already been submitted.');

        $data = $request->validate([
            'question_id' => ['required', 'uuid', 'exists:questions,id'],
            'selected_option_id' => ['nullable', 'uuid', 'exists:options,id'],
            'selected_option_ids' => ['nullable', 'array'],
            'selected_option_ids.*' => ['uuid', 'exists:options,id'],
            'nat_value' => ['nullable', 'numeric'],
            'descriptive_text' => ['nullable', 'string'],
            'is_marked_review' => ['boolean'],
            'visited' => ['boolean'],
            'time_spent_sec' => ['nullable', 'integer', 'min:0'],
        ]);

        $answer = Answer::updateOrCreate(
            ['session_id' => $session->id, 'question_id' => $data['question_id']],
            array_merge($data, ['answered_at' => now()]),
        );

        $session->update(['current_question_id' => $data['question_id'], 'time_remaining_sec' => $this->timer->remainingSeconds($session)]);

        return response()->json(['saved' => true, 'answer' => $answer, 'updated_at' => $answer->updated_at]);
    }

    public function submit(Request $request, ExamSession $session)
    {
        $this->authorizeSession($request, $session);

        $session->update([
            'submitted_at' => now(),
            'time_remaining_sec' => 0,
            'status' => 'submitted',
        ]);

        $result = $this->grading->gradeSession($session->fresh());

        return response()->json(['submitted' => true, 'result_id' => $result->id]);
    }

    public function summary(Request $request, ExamSession $session)
    {
        $this->authorizeSession($request, $session);
        $answers = $session->answers;

        return response()->json([
            'answered' => $answers->filter(fn ($answer) => $answer->selected_option_id || $answer->nat_value !== null || filled($answer->descriptive_text))->count(),
            'unanswered' => max(0, $session->exam->questions()->count() - $answers->count()),
            'marked_review' => $answers->where('is_marked_review', true)->count(),
        ]);
    }

    private function authorizeSession(Request $request, ExamSession $session): void
    {
        abort_unless($request->user()->role !== 'student' || $session->user_id === $request->user()->id, 403);
    }
}
