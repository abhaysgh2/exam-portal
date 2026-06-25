<?php

namespace App\Services;

use App\Models\ExamSession;

class ExamTimerService
{
    public function remainingSeconds(ExamSession $session): int
    {
        if ($session->submitted_at) {
            return 0;
        }

        $duration = $session->exam->duration_minutes * 60;
        $elapsed = $session->started_at->diffInSeconds(now());

        return max(0, $duration - $elapsed);
    }
}
