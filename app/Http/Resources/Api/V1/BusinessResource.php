<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
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
            'description' => $this->description,
            'logo' => $this->logo,
            'favicon' => $this->favicon,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'invoice_prefix' => $this->invoice_prefix,
            'invoice_terms' => $this->invoice_terms,
            'tax_label' => $this->tax_label,
            'tax_percentage' => $this->tax_percentage,
            'currency_symbol' => $this->currency_symbol,
            'currency_code' => $this->currency_code,
            'currency_country' => $this->currency_country,
            'social_links' => $this->social_links,
            'bank_details' => $this->bank_details,
            'default_invoice_notes' => $this->default_invoice_notes,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'industry' => $this->industry,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
