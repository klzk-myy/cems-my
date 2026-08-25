<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lean representation of a KYC document for nested customer payloads.
 *
 * Deliberately excludes file_path and file_hash so filesystem locations
 * and integrity digests never leave the compliance boundary.
 */
class CustomerDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'document_type' => $this->document_type,
            'status' => $this->status,
            'file_size' => $this->file_size,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
