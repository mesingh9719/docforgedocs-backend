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
            $data['logo'] = $this->processAndUploadImage($data['logo'], 'business/' . $business->id . '/logo');
        }

        if (isset($data['favicon']) && $data['favicon'] instanceof \Illuminate\Http\UploadedFile) {
            // Favicon logic remains specific or detailed, usually we don't compress favicons heavily or convert to webp if .ico is needed,
            // but for specific processing we can use default store or same logic. 
            // For now keeping it standard or similar.
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
            $logoUrl = $this->processAndUploadImage($data['logo'], 'business/' . $business->id . '/logo');
            $business->update(['logo' => $logoUrl]);
        }

        return $business;
    }

    /**
     * Process and upload image (Convert to WebP and Compress).
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $pathPrefix
     * @return string
     */
    protected function processAndUploadImage(\Illuminate\Http\UploadedFile $file, string $pathPrefix): string
    {
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());

        $image = $manager->read($file);

        // Encode to WebP with 75% quality
        $encoded = $image->toWebp(75);

        // Generate filename
        $filename = \Illuminate\Support\Str::random(40) . '.webp';
        $fullPath = $pathPrefix . '/' . $filename;

        // Store
        \Illuminate\Support\Facades\Storage::disk('public')->put($fullPath, (string) $encoded);

        // Return URL
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        return $disk->url($fullPath);
    }
}
