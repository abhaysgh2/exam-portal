<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Services\GradingService;
use Illuminate\Http\Request;

class GradingController extends Controller
{
    public function __construct(private GradingService $grading) {}

    public function queue()
    {
        return response()->json(
            Answer::with('question', 'session.user')
                ->whereHas('question', fn ($query) => $query->where('type', 'descriptive'))
                ->whereNull('manual_score')
                ->latest()
                ->paginate(),
        );
    }

    public function grade(Request $request, Answer $answer)
    {
        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $answer->update([
            'manual_score' => $data['score'],
            'manual_feedback' => $data['feedback'] ?? null,
        ]);

        $result = $this->grading->gradeSession($answer->session);

        return response()->json(['answer' => $answer->fresh(), 'result' => $result]);
    }
}
