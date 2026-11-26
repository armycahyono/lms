<?php

use App\Http\Controllers\Api\V1\MobileAuthController;
use App\Http\Controllers\Api\V1\MobileCourseController;
use App\Http\Controllers\Api\V1\SiapRecapController;
use App\Http\Controllers\Api\V1\SiapWebhookController;
use App\Http\Controllers\Api\V1\SignedFileController;
use App\Http\Middleware\VerifySiapWebhookToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('/webhooks/siap', [SiapWebhookController::class, 'handle'])
        ->middleware(VerifySiapWebhookToken::class);

    Route::prefix('integration/siap')
        ->middleware(VerifySiapWebhookToken::class)
        ->group(function () {
            Route::get('/grade-recap', [SiapRecapController::class, 'gradeRecap']);
            Route::get('/class-activity-summary', [SiapRecapController::class, 'classActivitySummary']);
        });

    Route::post('/auth/login', [MobileAuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('signed')->prefix('signed-files')->name('api.v1.signed-files.')->group(function () {
        Route::get('/materials/{material}', [SignedFileController::class, 'material'])->name('material');
        Route::get('/assignments/{assignment}', [SignedFileController::class, 'assignment'])->name('assignment');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/auth/change-password', [MobileAuthController::class, 'changePassword']);
        Route::post('/auth/logout', [MobileAuthController::class, 'logout']);

        Route::get('/courses', [MobileCourseController::class, 'index']);
        Route::get('/courses/{course}', [MobileCourseController::class, 'show']);
        Route::post('/materials/{material}/complete', [MobileCourseController::class, 'completeMaterial']);
        Route::post('/assignments/{assignment}/submit', [MobileCourseController::class, 'submitAssignment']);
    });
});
