<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $exams = Exam::query()
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('category')->toString(), fn ($query, $category) => $query->where('category', $category))
            ->latest()
            ->paginate($request->integer('limit', 20));

        return response()->json($exams);
    }

    public function store(Request $request)
    {
        $data = $this->validatedExam($request);
        $data['created_by'] = $request->user()->id;

        return response()->json(Exam::create($data), 201);
    }

    public function show(Request $request, Exam $exam)
    {
        if ($request->user()->role === 'student') {
            return response()->json($exam->load('sections'));
        }

        return response()->json($exam->load('sections', 'questions.options'));
    }

    public function update(Request $request, Exam $exam)
    {
        abort_if($exam->status !== 'draft', 409, 'Only draft exams can be edited.');

        $data = $this->validatedExam($request, partial: true);
        $this->ensureInstantResultsAreReady($exam, $data);

        $exam->update($data);

        return response()->json($exam->fresh());
    }

    public function updateInstantResults(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        if ($data['enabled']) {
            abort_unless($this->hasObjectiveAnswerKeys($exam), 422, 'please first upload the answers');
        }

        $exam->update([
            'show_results_after' => $data['enabled'] ? 'submit' : 'manual_release',
            'results_release_at' => $data['enabled'] ? null : $exam->results_release_at,
        ]);

        return response()->json([
            'exam' => $exam->fresh(),
            'instant_results_enabled' => $data['enabled'],
            'message' => $data['enabled'] ? 'Instant results enabled.' : 'Instant results disabled.',
        ]);
    }

    public function destroy(Exam $exam)
    {
        abort_if($exam->status === 'live', 409, 'Live exams cannot be deleted.');
        $exam->delete();

        return response()->noContent();
    }

    public function publish(Exam $exam)
    {
        abort_if($exam->questions()->count() === 0, 422, 'Add questions before publishing.');
        abort_if($exam->show_results_after === 'submit' && ! $this->hasObjectiveAnswerKeys($exam), 422, 'please first upload the answers');

        $exam->update(['status' => 'scheduled']);

        return response()->json($exam->fresh());
    }

    public function registerForExam(Request $request, Exam $exam)
    {
        abort_unless(in_array($exam->status, ['scheduled', 'live'], true), 409, 'Registration is only open for scheduled or live exams.');
        abort_if($exam->end_time && now()->gt($exam->end_time), 409, 'Registration is closed for this exam.');
        abort_if($exam->registrations()->count() >= $exam->max_candidates, 409, 'Exam capacity reached.');

        $registration = Registration::firstOrCreate(
            ['exam_id' => $exam->id, 'user_id' => $request->user()->id],
            ['status' => 'registered', 'registered_at' => now()],
        );

        return response()->json($registration, 201);
    }

    public function withdraw(Request $request, Exam $exam)
    {
        Registration::where('exam_id', $exam->id)->where('user_id', $request->user()->id)->delete();

        return response()->noContent();
    }

    public function myRegistrations(Request $request)
    {
        return response()->json(
            Registration::with('exam')->where('user_id', $request->user()->id)->latest('registered_at')->paginate(),
        );
    }

    public function registrations(Exam $exam)
    {
        return response()->json($exam->registrations()->with('user')->paginate());
    }

    public function results(Exam $exam)
    {
        return response()->json($exam->sessions()->with('result', 'user')->paginate());
    }

    public function leaderboard(Exam $exam)
    {
        return response()->json(
            $exam->sessions()->with('user', 'result')->whereHas('result')->get()
                ->sortByDesc(fn ($session) => $session->result->final_score)
                ->values(),
        );
    }

    public function stats(Exam $exam)
    {
        return response()->json([
            'registrations' => $exam->registrations()->count(),
            'sessions_started' => $exam->sessions()->count(),
            'submitted' => $exam->sessions()->whereNotNull('submitted_at')->count(),
            'proctoring_flags' => $exam->sessions()->join('proctoring_logs', 'exam_sessions.id', '=', 'proctoring_logs.session_id')->count(),
        ]);
    }

    public function releaseResults(Exam $exam)
    {
        $exam->update(['show_results_after' => 'submit', 'results_release_at' => now()]);

        return response()->json($exam->fresh());
    }

    private function validatedExam(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'duration_minutes' => [$required, 'integer', 'min:1'],
            'total_marks' => [$required, 'numeric', 'min:0'],
            'pass_marks' => ['nullable', 'numeric', 'min:0'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'max_candidates' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'live', 'completed', 'archived'])],
            'instructions' => ['nullable', 'string'],
            'randomize_questions' => ['boolean'],
            'randomize_options' => ['boolean'],
            'allow_review' => ['boolean'],
            'show_results_after' => ['nullable', Rule::in(['submit', 'manual_release', 'schedule'])],
            'results_release_at' => ['nullable', 'date'],
        ]);
    }

    private function ensureInstantResultsAreReady(Exam $exam, array $data): void
    {
        if (($data['show_results_after'] ?? null) === 'submit') {
            abort_unless($this->hasObjectiveAnswerKeys($exam), 422, 'please first upload the answers');
        }
    }

    private function hasObjectiveAnswerKeys(Exam $exam): bool
    {
        $questions = $exam->questions()->with('options', 'natAnswer')->get();

        if ($questions->isEmpty()) {
            return false;
        }

        return $questions->every(function ($question): bool {
            return match ($question->type) {
                'mcq', 'multi_correct' => $question->options->contains('is_correct', true),
                'nat' => $question->natAnswer !== null,
                'descriptive' => false,
                default => false,
            };
        });
    }
}
