<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;

class AnalyticsController extends Controller
{
    public function examOverview(Exam $exam)
    {
        return response()->json([
            'registrations' => $exam->registrations()->count(),
            'active_sessions' => $exam->sessions()->where('status', 'in_progress')->count(),
            'submitted_sessions' => $exam->sessions()->whereNotNull('submitted_at')->count(),
            'average_score' => round((float) $exam->sessions()->join('results', 'exam_sessions.id', '=', 'results.session_id')->avg('results.final_score'), 2),
            'high_severity_flags' => $exam->sessions()->join('proctoring_logs', 'exam_sessions.id', '=', 'proctoring_logs.session_id')->whereIn('severity', ['high', 'critical'])->count(),
        ]);
    }
}
