<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ExamGroupController;
use App\Http\Controllers\Api\GradingController;
use App\Http\Controllers\Api\ProctoringController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/renew', [AuthController::class, 'renew'])->middleware('throttle:10,1');
        Route::get('me', [AuthController::class, 'me']);

        Route::get('exams', [ExamController::class, 'index']);
        Route::get('exams/{exam}', [ExamController::class, 'show']);
        Route::post('exams/{exam}/register', [ExamController::class, 'registerForExam'])->middleware('role:student,admin');
        Route::delete('exams/{exam}/register', [ExamController::class, 'withdraw'])->middleware('role:student,admin');
        Route::get('my/registrations', [ExamController::class, 'myRegistrations']);

        Route::post('sessions/start', [SessionController::class, 'start'])->middleware(['role:student,admin', 'throttle:30,1']);
        Route::get('sessions/{session}', [SessionController::class, 'show']);
        Route::patch('sessions/{session}/answer', [SessionController::class, 'answer'])->middleware('throttle:120,1');
        Route::post('sessions/{session}/submit', [SessionController::class, 'submit']);
        Route::get('sessions/{session}/summary', [SessionController::class, 'summary']);

        Route::post('proctoring/flag', [ProctoringController::class, 'store'])->middleware('throttle:120,1');

        Route::middleware('role:examiner,admin')->group(function (): void {
            Route::post('exams', [ExamController::class, 'store']);
            Route::put('exams/{exam}', [ExamController::class, 'update']);
            Route::post('exams/{exam}/questions', [ExamController::class, 'addQuestion']);
            Route::patch('exams/{exam}/instant-results', [ExamController::class, 'updateInstantResults']);
            Route::get('exams/{exam}/stats', [ExamController::class, 'stats']);
            Route::get('exams/{exam}/registrations', [ExamController::class, 'registrations']);
            Route::get('exams/{exam}/results', [ExamController::class, 'results']);
            Route::get('exams/{exam}/submissions', [ExamController::class, 'submissions']);
            Route::get('exams/{exam}/leaderboard', [ExamController::class, 'leaderboard']);
            Route::get('grading/queue', [GradingController::class, 'queue']);
            Route::post('grading/{answer}', [GradingController::class, 'grade']);
            Route::get('proctoring/flags', [ProctoringController::class, 'index']);
            Route::put('proctoring/flags/{flag}/action', [ProctoringController::class, 'action']);
            Route::get('analytics/exams/{exam}/overview', [AnalyticsController::class, 'examOverview']);
            Route::post('reports/generate', [ReportController::class, 'generate']);
            Route::post('exams/{exam}/publish', [ExamController::class, 'publish']);
            Route::get('exam-groups', [ExamGroupController::class, 'index']);
            Route::post('exam-groups', [ExamGroupController::class, 'store']);
            Route::put('exam-groups/{group}', [ExamGroupController::class, 'update']);
            Route::delete('exam-groups/{group}', [ExamGroupController::class, 'destroy']);
            Route::post('exam-groups/{group}/exams', [ExamGroupController::class, 'attachExam']);
            Route::delete('exam-groups/{group}/exams/{exam}', [ExamGroupController::class, 'detachExam']);
            Route::post('exam-groups/{group}/students', [ExamGroupController::class, 'attachStudent']);
            Route::delete('exam-groups/{group}/students/{user}', [ExamGroupController::class, 'detachStudent']);
        });

        Route::middleware('role:admin')->group(function (): void {
            Route::delete('exams/{exam}', [ExamController::class, 'destroy']);
            Route::post('exams/{exam}/results/release', [ExamController::class, 'releaseResults']);
            Route::apiResource('users', UserController::class)->except(['create', 'edit']);
            Route::post('users/{user}/suspend', [UserController::class, 'suspend']);
            Route::post('users/{user}/activate', [UserController::class, 'activate']);
        });
    });
});
