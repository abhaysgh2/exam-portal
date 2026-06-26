<?php

namespace App\Services;

use App\Models\ExamSession;

class ExamTimerService
{
    public const ANSWER_GRACE_SECONDS = 60;

    public function remainingSeconds(ExamSession $session): int
    {
        if ($session->submitted_at) {
            return 0;
        }

        $duration = $session->exam->duration_minutes * 60;
        $elapsed = $session->started_at->diffInSeconds(now());

        return max(0, $duration - $elapsed);
    }

    public function isPastAnswerGrace(ExamSession $session): bool
    {
        if ($session->submitted_at) {
            return true;
        }

        $duration = $session->exam->duration_minutes * 60;
        $elapsed = $session->started_at->diffInSeconds(now());

        return $elapsed > $duration + self::ANSWER_GRACE_SECONDS;
    }
}
