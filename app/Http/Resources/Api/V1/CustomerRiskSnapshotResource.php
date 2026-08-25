<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lean representation of a risk score snapshot for nested customer payloads.
 */
class CustomerRiskSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'snapshot_date' => $this->snapshot_date,
            'overall_score' => $this->overall_score,
            'velocity_score' => $this->velocity_score,
            'structuring_score' => $this->structuring_score,
            'geographic_score' => $this->geographic_score,
            'amount_score' => $this->amount_score,
            'trend' => $this->trend,
            'factors' => $this->factors,
            'next_screening_date' => $this->next_screening_date,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
