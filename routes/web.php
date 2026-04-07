<?php

use Illuminate\Support\Facades\Route;

// Admin routes are now handled in routes/admin.php


Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/test-chat', function () {
    return view('chat_test');
});
Route::get('/chat2', function () {
    return view('chat-test2');
});

// Workaround for Windows php artisan serve symlink 403 Forbidden issue
// Renamed from 'storage' to 'media' to avoid prefix hijacking in some environments
Route::get('media/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    
    $file = file_get_contents($fullPath);
    $type = mime_content_type($fullPath);
    
    return response($file, 200)->header('Content-Type', $type)
        ->header('Cache-Control', 'public, max-age=86400')
        ->header('Access-Control-Allow-Origin', '*');
})->where('path', '.*');