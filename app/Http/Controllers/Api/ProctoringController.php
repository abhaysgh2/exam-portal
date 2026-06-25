<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ProctoringLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProctoringController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'uuid', 'exists:exam_sessions,id'],
            'event_type' => ['required', 'string', 'max:50'],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'details' => ['nullable', 'array'],
            'screenshot_url' => ['nullable', 'url'],
        ]);

        $session = ExamSession::findOrFail($data['session_id']);
        abort_unless($request->user()->role !== 'student' || $session->user_id === $request->user()->id, 403);

        return response()->json(ProctoringLog::create($data), 201);
    }

    public function index(Request $request)
    {
        $logs = ProctoringLog::query()
            ->when($request->query('severity'), fn ($query, $severity) => $query->where('severity', $severity))
            ->when($request->query('exam_id'), function ($query, $examId): void {
                $query->whereIn('session_id', ExamSession::where('exam_id', $examId)->select('id'));
            })
            ->latest('logged_at')
            ->paginate();

        return response()->json($logs);
    }

    public function action(Request $request, ProctoringLog $flag)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['warn', 'disqualify', 'dismiss'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $flag->update(['action' => $data['action'], 'action_note' => $data['note'] ?? null]);

        if ($data['action'] === 'disqualify') {
            ExamSession::whereKey($flag->session_id)->update(['status' => 'disqualified']);
        }

        return response()->json($flag->fresh());
    }
}
