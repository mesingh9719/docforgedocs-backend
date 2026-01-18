<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BusinessService
{
    /**
     * Update business information.
     *
     * @param int $businessId
     * @param array $data
     * @return Business
     */
    public function updateBusiness(int $businessId, array $data): Business
    {
        $business = Business::findOrFail($businessId);

        if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $data['logo']->store('business/' . $business->id . '/logo', 'public');
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $data['logo'] = $disk->url($path);
        }

        if (isset($data['favicon']) && $data['favicon'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $data['favicon']->store('business/' . $business->id . '/favicon', 'public');
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $data['favicon'] = $disk->url($path);
        }

        $business->update($data);

        return $business;
    }
    /**
     * Create a new business and link it to the user.
     *
     * @param \App\Models\User $user
     * @param array $data
     * @return Business
     */
    public function createBusiness(\App\Models\User $user, array $data): Business
    {
        // 1. Create with basic data first to get ID
        $businessData = [
            'user_id' => $user->id,
            'name' => $data['name'],
            'industry' => $data['industry'] ?? null,
            'size' => $data['size'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'website' => $data['website'] ?? null,
            // Set defaults/others if needed
        ];

        $business = Business::create($businessData);

        // 2. Handle Logo Upload if present
        if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $data['logo']->store('business/' . $business->id . '/logo', 'public');
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $business->update(['logo' => $disk->url($path)]);
        }

        // 3. Link user as owner in child_users (already handled by creating? No, createBusiness usually implies the user IS the owner)
        // The check in BusinessController says if(!$user->resolveBusiness()), implying 1-to-1 or owned relation.
        // We should ensure the relationship is established if needed, but assuming resolveBusiness checks user_id on business table:
        // schema: user_id foreign key exists. So we are good.

        return $business;
    }
}
