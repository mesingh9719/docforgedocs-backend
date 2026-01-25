<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'business' => new BusinessResource($this->resource->resolveBusiness()),
            // Add Role/Permissions context
            'role' => $this->resource->resolveRole(),
            'permissions' => $this->resource->resolvePermissions(),
            'is_platform_admin' => $this->is_platform_admin,
            'documents_count' => $this->documents_count,
            'child_users_count' => $this->child_users_count,
            'parent_user' => ($this->relationLoaded('parentUser') && $this->parentUser)
                ? new UserResource($this->parentUser->parent)
                : null,
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'child_users' => UserResource::collection($this->whenLoaded('children')),
        ];
    }
}
