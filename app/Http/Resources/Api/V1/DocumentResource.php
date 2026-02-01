<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
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
            'name' => $this->resource->name, // Explicitly expose name
            'title' => $this->resource->name, // Alias name to title for frontend
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'content' => $this->resource->content,
            'status' => $this->resource->status,
            'type' => $this->resource->documentType ? [
                'id' => $this->resource->documentType->id,
                'name' => $this->resource->documentType->name,
                'slug' => $this->resource->documentType->slug,
            ] : null,
            'business' => new BusinessResource($this->resource->business),
            'creator' => $this->resource->creator ? [
                'id' => $this->resource->creator->id,
                'name' => $this->resource->creator->name,
                'email' => $this->resource->creator->email,
            ] : null,
            'pdf_url' => $this->resource->pdf_url,
            'final_pdf_url' => route('documents.pdf', $this->resource->id),
            'fields' => $this->whenLoaded('fields'),
            'signers' => $this->whenLoaded('signers'),
            'signers_count' => $this->resource->signers_count,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
