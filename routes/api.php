<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\BusinessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    // Public Routes
    Route::get('/public/documents/{token}', [App\Http\Controllers\Api\V1\PublicDocumentController::class, 'show']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/accept-invite', [TeamController::class, 'acceptInvite']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);
        Route::post('/business-update', [AuthController::class, 'businessUpdate']);
        Route::get('/user', function (Request $request) {
            return new \App\Http\Resources\Api\V1\UserResource($request->user());
        });
        Route::get('/documents/next-invoice-number', [DocumentController::class, 'getNextInvoiceNumber']);
        Route::apiResource('documents', DocumentController::class);
        Route::post('documents/{document}/generate-pdf', [DocumentController::class, 'generatePdf']);
        Route::post('documents/{document}/send', [DocumentController::class, 'send']);
        Route::post('documents/{document}/remind', [DocumentController::class, 'remind']);
        Route::apiResource('team', TeamController::class)->except(['show']); // No show needed
        Route::get('/permissions', [\App\Http\Controllers\Api\V1\PermissionController::class, 'index']);
        Route::get('/dashboard/stats', [\App\Http\Controllers\Api\V1\DashboardController::class, 'stats']);
        Route::get('/dashboard/activity', [\App\Http\Controllers\Api\V1\DashboardController::class, 'activity']);

        Route::post('businesses', [BusinessController::class, 'store']);
        Route::put('business', [BusinessController::class, 'update']);
    });
});
