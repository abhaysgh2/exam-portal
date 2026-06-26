<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', fn () => response()->json([
    'name' => config('app.name', 'Exam Portal'),
    'status' => 'running',
    'message' => 'Exam Portal backend API is running.',
    'health' => url('/health'),
    'api' => url('/api/v1'),
    'docs' => [
        'local' => 'docs/LOCAL_DEVELOPMENT.md',
        'features' => 'docs/FEATURES_AND_STEPS.md',
    ],
]));

Route::get('/health', fn () => response()->json([
    'name' => config('app.name', 'Exam Portal'),
    'status' => 'ok',
    'time' => now()->toISOString(),
]))->withoutMiddleware([
    AddQueuedCookiesToResponse::class,
    EncryptCookies::class,
    PreventRequestForgery::class,
    ValidateCsrfToken::class,
    VerifyCsrfToken::class,
    StartSession::class,
    ShareErrorsFromSession::class,
]);
