<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lean representation of a compliance flag for teller-facing payloads.
 *
 * Deliberately excludes notes, assigned_to, reviewed_by and reviewer
 * attribution - those are restricted to compliance workflows.
 */
class FlagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'flag_type' => $this->flag_type,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
