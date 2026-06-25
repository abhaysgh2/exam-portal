<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function generate(Request $request)
    {
        $data = $request->validate([
            'exam_id' => ['required', 'uuid', 'exists:exams,id'],
            'report_type' => ['required', Rule::in(['score', 'item_analysis', 'proctoring_audit'])],
            'format' => ['required', Rule::in(['pdf', 'csv'])],
        ]);

        return response()->json([
            'job_id' => (string) Str::uuid(),
            'status' => 'queued',
            'request' => $data,
        ], 202);
    }
}
