<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBusinessRequest;
use App\Http\Requests\Api\V1\BusinessUpdateRequest;
use App\Http\Resources\Api\V1\BusinessResource;
use App\Services\BusinessService;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function __construct(
        protected BusinessService $businessService
    ) {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessRequest $request)
    {
        $user = $request->user();

        if ($user->resolveBusiness()) {
            return response()->json(['message' => 'User already has a business.'], 400);
        }

        $business = $this->businessService->createBusiness($user, $request->validated());

        return response()->json([
            'message' => 'Business created successfully.',
            'data' => new BusinessResource($business),
        ], 201);
    }

    /**
     * Update business information.
     */
    public function update(BusinessUpdateRequest $request)
    {
        // Resolve business first to pass to policy
        $user = $request->user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'User does not have a business.'], 403);
        }

        // Authorize with policy
        $this->authorize('update', $business);

        $business = $this->businessService->updateBusiness($business->id, $request->validated());

        return response()->json([
            'message' => 'Business updated successfully.',
            'data' => new BusinessResource($business),
        ]);
    }
}
