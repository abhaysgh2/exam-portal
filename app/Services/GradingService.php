<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\ExamSession;
use App\Models\Result;

class GradingService
{
    public function gradeSession(ExamSession $session): Result
    {
        $session->loadMissing('exam.questions.options', 'exam.questions.natAnswer', 'answers');

        $score = 0.0;
        $correct = 0;
        $wrong = 0;
        $unattempted = 0;

        foreach ($session->exam->questions as $question) {
            $answer = $session->answers->firstWhere('question_id', $question->id);
            $points = $this->gradeAnswer($answer, $question);
            $score += $points;

            if (! $answer || $this->isUnattempted($answer)) {
                $unattempted++;
            } elseif ($points > 0) {
                $correct++;
            } else {
                $wrong++;
            }
        }

        $totalQuestions = max(1, $session->exam->questions->count());
        $accuracy = round(($correct / $totalQuestions) * 100, 2);
        $timeTaken = $session->submitted_at
            ? $session->submitted_at->diffInSeconds($session->started_at)
            : $session->exam->duration_minutes * 60;

        return Result::updateOrCreate(
            ['session_id' => $session->id],
            [
                'exam_id' => $session->exam_id,
                'user_id' => $session->user_id,
                'raw_score' => $score,
                'final_score' => $score,
                'total_correct' => $correct,
                'total_wrong' => $wrong,
                'total_unattempted' => $unattempted,
                'total_questions' => $session->exam->questions->count(),
                'accuracy_pct' => $accuracy,
                'time_taken_sec' => $timeTaken,
                'is_pass' => $session->exam->pass_marks === null || $score >= (float) $session->exam->pass_marks,
                'computed_at' => now(),
            ],
        );
    }

    public function gradeAnswer(?Answer $answer, $question): float
    {
        if (! $answer || $this->isUnattempted($answer)) {
            return 0.0;
        }

        return match ($question->type) {
            'mcq' => $answer->selected_option_id && $question->options->firstWhere('id', $answer->selected_option_id)?->is_correct
                ? (float) $question->marks
                : -abs((float) $question->negative_marks),
            'multi_correct' => $this->gradeMultiCorrect($answer, $question),
            'nat' => $this->gradeNat($answer, $question),
            'descriptive' => $answer->manual_score === null ? 0.0 : (float) $answer->manual_score,
            default => 0.0,
        };
    }

    private function gradeMultiCorrect(Answer $answer, $question): float
    {
        $selected = collect($answer->selected_option_ids ?? [])->sort()->values()->all();
        $correct = $question->options->where('is_correct', true)->pluck('id')->sort()->values()->all();

        return $selected === $correct ? (float) $question->marks : -abs((float) $question->negative_marks);
    }

    private function gradeNat(Answer $answer, $question): float
    {
        if ($answer->nat_value === null || ! $question->natAnswer) {
            return 0.0;
        }

        $delta = abs((float) $answer->nat_value - (float) $question->natAnswer->correct_value);

        return $delta <= (float) $question->natAnswer->tolerance
            ? (float) $question->marks
            : -abs((float) $question->negative_marks);
    }

    private function isUnattempted(Answer $answer): bool
    {
        return $answer->selected_option_id === null
            && empty($answer->selected_option_ids)
            && $answer->nat_value === null
            && blank($answer->descriptive_text);
    }
}
