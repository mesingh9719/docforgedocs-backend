<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AdminDocumentController;
use App\Http\Controllers\Api\V1\AdminMasterDataController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\MasterDataController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\SignatureController;
use App\Http\Controllers\Api\V1\PublicDocumentController;
use App\Http\Controllers\Api\V1\PublicCmsController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\AdminSettingController;
use App\Http\Controllers\Api\V1\CmsController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\BusinessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    // Public Routes
    Route::get('/file-proxy', [DocumentController::class, 'getStorageFile']);
    Route::get('/public/documents/{token}', [PublicDocumentController::class, 'show'])->name('api.v1.public.documents.show');
    Route::get('/public/documents/{token}/preview', [PublicDocumentController::class, 'preview'])->name('api.v1.public.documents.preview');
    Route::get('/public/documents/{token}/download', [PublicDocumentController::class, 'download'])->name('api.v1.public.documents.download');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);
    Route::post('/accept-invite', [TeamController::class, 'acceptInvite']);
    Route::get('/documents/{document}/pdf', [DocumentController::class, 'stream'])
        ->name('documents.pdf');

    // Email Verification
    // Email Verification
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->name('verification.verify');

    // Email Resend
    Route::post('/email/resend', [VerificationController::class, 'resend'])
        ->middleware(['auth:sanctum']);

    // Public Signature Routes
    Route::get('/signatures/{token}', [SignatureController::class, 'show'])->name('api.v1.signatures.show');
    Route::get('/signatures/{token}/preview', [SignatureController::class, 'preview'])->name('api.v1.signatures.preview');
    Route::get('/signatures/{token}/download-signed', [SignatureController::class, 'downloadSigned'])->name('api.v1.signatures.download_signed');
    Route::post('/signatures/{token}/sign', [SignatureController::class, 'sign'])->name('api.v1.signatures.sign');
    Route::post('/signatures/{token}/agree', [SignatureController::class, 'agreeToTerms'])->name('api.v1.signatures.agree');
    Route::post('/signatures/{token}/send-otp', [SignatureController::class, 'sendOtp'])->name('api.v1.signatures.send_otp');
    Route::post('/signatures/{token}/verify-otp', [SignatureController::class, 'verifyOtp'])->name('api.v1.signatures.verify_otp');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/user/profile', [AuthController::class, 'updateProfile']);
        Route::post('/business-update', [AuthController::class, 'businessUpdate']);
        Route::get('/user', function (Request $request) {
            return new UserResource($request->user());
        });

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

        // Master Data Routes
        Route::get('/currencies', [MasterDataController::class, 'currencies']);
        Route::get('/tax-rates', [MasterDataController::class, 'taxRates']);

        // Admin Routes
        Route::middleware([EnsurePlatformAdmin::class])->group(function () {
            Route::get('/admin/stats', [AdminController::class, 'stats']);
            Route::get('/admin/activities', [AdminController::class, 'activities']);
            Route::get('/admin/settings', [AdminSettingController::class, 'index']);
            Route::post('/admin/settings', [AdminSettingController::class, 'update']);
            Route::apiResource('/admin/users', AdminUserController::class)->names('admin.users');
            Route::apiResource('/admin/documents', AdminDocumentController::class)->names('admin.documents');

            // Master Data Routes
            Route::controller(AdminMasterDataController::class)->prefix('admin/master-data')->group(function () {
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

                // Team Management
                Route::get('/team', [TeamController::class, 'index']);
                Route::post('/team/invite', [TeamController::class, 'invite']);
                Route::post('/team/{childUser}', [TeamController::class, 'update']);
                Route::delete('/team/{childUser}', [TeamController::class, 'destroy']);

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
        // Signature Workflow
        Route::get('/signatures', [SignatureController::class, 'index']);
        Route::get('/signatures/{documentId}/view-signed', [SignatureController::class, 'viewSigned']);



        Route::get('/signatures/{documentId}/download-pdf', [SignatureController::class, 'downloadPdf']);
        Route::post('/signatures/upload', [SignatureController::class, 'upload']);
        Route::post('/signatures/send', [SignatureController::class, 'send']);

        Route::post('documents/{document}/generate-pdf', [DocumentController::class, 'generatePdf']);
        Route::post('documents/{document}/send', [DocumentController::class, 'send']);
        Route::post('documents/{document}/remind', [DocumentController::class, 'remind']);
        Route::post('documents/bulk-delete', [DocumentController::class, 'bulkDestroy']);
        Route::post('documents/{document}/restore', [DocumentController::class, 'restore']);
        Route::get('documents/{document}/versions', [DocumentController::class, 'getVersions']);
        Route::get('documents/{document}/shares', [DocumentController::class, 'getShares']);
        Route::post('documents/{document}/restore/{version}', [DocumentController::class, 'restoreVersion']);

        // Route::apiResource('signatures', \App\Http\Controllers\Api\V1\UserSignatureController::class);
        Route::apiResource('team', TeamController::class)->except(['show']); // No show needed
        Route::apiResource('roles', RoleController::class);
        Route::get('/permissions', [PermissionController::class, 'index']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
        Route::get('/dashboard/activity', [DashboardController::class, 'activity']);

        Route::post('documents/{document}/duplicate', [DocumentController::class, 'duplicate']);
        Route::get('documents/export', [DocumentController::class, 'export']);
        Route::apiResource('documents', DocumentController::class);



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
