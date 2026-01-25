<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CmsController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\PublicCmsController;
use App\Http\Controllers\Api\V1\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    // Public Routes
    Route::get('/public/documents/{token}', [App\Http\Controllers\Api\V1\PublicDocumentController::class, 'show']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [App\Http\Controllers\Api\V1\PasswordResetController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [App\Http\Controllers\Api\V1\PasswordResetController::class, 'reset']);
    Route::post('/accept-invite', [TeamController::class, 'acceptInvite']);

    // Email Verification
    // Email Verification
    Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Api\V1\VerificationController::class, 'verify'])
        ->name('verification.verify');

    // Email Resend
    Route::post('/email/resend', [\App\Http\Controllers\Api\V1\VerificationController::class, 'resend'])
        ->middleware(['auth:sanctum']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);
        Route::post('/business-update', [AuthController::class, 'businessUpdate']);
        Route::get('/user', function (Request $request) {
            return new \App\Http\Resources\Api\V1\UserResource($request->user());
        });

        // Master Data Routes
        Route::get('/currencies', [\App\Http\Controllers\Api\V1\MasterDataController::class, 'currencies']);
        Route::get('/tax-rates', [\App\Http\Controllers\Api\V1\MasterDataController::class, 'taxRates']);

        // Admin Routes
        Route::middleware([\App\Http\Middleware\EnsurePlatformAdmin::class])->group(function () {
            Route::get('/admin/stats', [\App\Http\Controllers\Api\V1\AdminController::class, 'stats']);
            Route::get('/admin/activities', [\App\Http\Controllers\Api\V1\AdminController::class, 'activities']);
            Route::get('/admin/settings', [\App\Http\Controllers\Api\V1\AdminSettingController::class, 'index']);
            Route::post('/admin/settings', [\App\Http\Controllers\Api\V1\AdminSettingController::class, 'update']);
            Route::apiResource('/admin/users', \App\Http\Controllers\Api\V1\AdminUserController::class);
            Route::apiResource('/admin/documents', \App\Http\Controllers\Api\V1\AdminDocumentController::class);

            // Master Data Routes
            Route::controller(\App\Http\Controllers\Api\V1\AdminMasterDataController::class)->prefix('admin/master-data')->group(function () {
                Route::get('{type}', 'index');
                Route::post('{type}', 'store');
                Route::put('{type}/{id}', 'update');
                Route::delete('{type}/{id}', 'destroy');
            });

            // CMS Routes (Admin)
            Route::prefix('cms')->group(function () {
                // Categories
                Route::get('/categories', [CmsController::class, 'indexCategories']);
                Route::post('/categories', [CmsController::class, 'storeCategory']);
                Route::put('/categories/{category}', [CmsController::class, 'updateCategory']);
                Route::delete('/categories/{category}', [CmsController::class, 'destroyCategory']);

                // Posts
                Route::get('/posts', [CmsController::class, 'indexPosts']);
                Route::post('/posts', [CmsController::class, 'storePost']);
                Route::get('/posts/{post}', [CmsController::class, 'showPost']);
                Route::put('/posts/{post}', [CmsController::class, 'updatePost']);
                Route::delete('/posts/{post}', [CmsController::class, 'destroyPost']);

                // Inquiries
                Route::get('/inquiries', [CmsController::class, 'indexInquiries']);
                Route::get('/inquiries/{inquiry}', [CmsController::class, 'showInquiry']);
                Route::put('/inquiries/{inquiry}/status', [CmsController::class, 'updateInquiryStatus']);

                // Settings
                Route::get('/settings', [CmsController::class, 'getSettings']);
                Route::post('/settings', [CmsController::class, 'updateSettings']);
            });
        });

        Route::get('/documents/next-invoice-number', [DocumentController::class, 'getNextInvoiceNumber']);
        Route::apiResource('documents', DocumentController::class);
        Route::post('documents/{document}/generate-pdf', [DocumentController::class, 'generatePdf']);
        Route::post('documents/{document}/send', [DocumentController::class, 'send']);
        Route::post('documents/{document}/remind', [DocumentController::class, 'remind']);
        Route::get('documents/{document}/versions', [DocumentController::class, 'getVersions']);
        Route::get('documents/{document}/shares', [DocumentController::class, 'getShares']);
        Route::post('documents/{document}/restore/{version}', [DocumentController::class, 'restoreVersion']);
        Route::apiResource('team', TeamController::class)->except(['show']); // No show needed
        Route::get('/permissions', [\App\Http\Controllers\Api\V1\PermissionController::class, 'index']);
        Route::get('/dashboard/stats', [\App\Http\Controllers\Api\V1\DashboardController::class, 'stats']);
        Route::get('/dashboard/analytics', [\App\Http\Controllers\Api\V1\DashboardController::class, 'analytics']);
        Route::get('/dashboard/activity', [\App\Http\Controllers\Api\V1\DashboardController::class, 'activity']);

        Route::post('businesses', [BusinessController::class, 'store']);
        Route::put('business', [BusinessController::class, 'update']);
    });

    // Public CMS Routes
    Route::prefix('public')->group(function () {
        Route::get('/categories', [PublicCmsController::class, 'getCategories']);
        Route::get('/posts', [PublicCmsController::class, 'getPosts']);
        Route::get('/posts/{slug}', [PublicCmsController::class, 'getPostBySlug']);
        Route::get('/contact-info', [PublicCmsController::class, 'getContactInfo']);
        Route::post('/contact', [PublicCmsController::class, 'submitContactForm']);
    });
});
