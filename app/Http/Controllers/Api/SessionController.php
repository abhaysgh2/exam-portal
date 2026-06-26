<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Option;
use App\Models\Question;
use App\Services\ExamTimerService;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SessionController extends Controller
{
    public function __construct(private ExamTimerService $timer, private GradingService $grading) {}

    public function start(Request $request)
    {
        $data = $request->validate(['exam_id' => ['required', 'uuid', 'exists:exams,id']]);
        $exam = Exam::with('questions.options', 'sections')->findOrFail($data['exam_id']);

        abort_unless($exam->status === 'live', 400, 'Exam is not live.');
        abort_if($exam->start_time && now()->lt($exam->start_time), 400, 'Exam has not started.');
        abort_if($exam->end_time && now()->gt($exam->end_time), 400, 'Exam has ended.');
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

        abort_if($session->submitted_at || $session->status !== 'in_progress', 409, 'Session is already closed.');
        abort_if($this->timer->isPastAnswerGrace($session), 409, 'Session time has expired.');

        return response()->json([
            'session_id' => $session->id,
            'questions' => $this->orderedQuestions($questions, $session->question_order ?? $order),
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
        $this->authorizeWritableSession($request, $session);
        $this->ensureSessionCanAcceptAnswers($session);

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

        $question = Question::whereKey($data['question_id'])
            ->where('exam_id', $session->exam_id)
            ->firstOrFail();

        if (! empty($data['selected_option_id'])) {
            abort_unless(
                Option::whereKey($data['selected_option_id'])->where('question_id', $question->id)->exists(),
                422,
                'Selected option does not belong to this question.',
            );
        }

        if (! empty($data['selected_option_ids'])) {
            $validOptionCount = Option::where('question_id', $question->id)
                ->whereIn('id', $data['selected_option_ids'])
                ->count();

            abort_unless($validOptionCount === count($data['selected_option_ids']), 422, 'One or more selected options do not belong to this question.');
        }

        $answer = Answer::updateOrCreate(
            ['session_id' => $session->id, 'question_id' => $data['question_id']],
            array_merge($data, ['answered_at' => now()]),
        );

        $session->update(['current_question_id' => $data['question_id'], 'time_remaining_sec' => $this->timer->remainingSeconds($session)]);

        return response()->json(['saved' => true, 'answer' => $answer, 'updated_at' => $answer->updated_at]);
    }

    public function submit(Request $request, ExamSession $session)
    {
        $this->authorizeWritableSession($request, $session);
        abort_if($session->submitted_at || $session->status !== 'in_progress', 409, 'Session is already closed.');

        $session->update([
            'submitted_at' => now(),
            'time_remaining_sec' => 0,
            'status' => 'submitted',
        ]);

        $session = $session->fresh('exam');
        $result = $this->grading->gradeSession($session);
        $resultVisible = $session->exam->show_results_after === 'submit';

        return response()->json([
            'submitted' => true,
            'result_id' => $result->id,
            'result_visible' => $resultVisible,
            'message' => $resultVisible
                ? 'Your result is ready.'
                : 'Your test is submitted and will be evaluated. You can exit the test.',
            'result' => $resultVisible ? $result : null,
        ]);
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

    private function authorizeWritableSession(Request $request, ExamSession $session): void
    {
        abort_unless($session->user_id === $request->user()->id, 403);
    }

    private function ensureSessionCanAcceptAnswers(ExamSession $session): void
    {
        abort_if($session->submitted_at || $session->status !== 'in_progress', 409, 'Session is already closed.');
        abort_if($this->timer->isPastAnswerGrace($session), 409, 'Session time has expired.');
    }

    private function orderedQuestions(Collection $questions, array $order): Collection
    {
        $positions = array_flip($order);

        return $questions
            ->sortBy(fn (Question $question) => $positions[$question->id] ?? PHP_INT_MAX)
            ->values();
    }
}
