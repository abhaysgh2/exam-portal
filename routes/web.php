<?php

use Illuminate\Support\Facades\Route;

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
