<?php

namespace App\Http\Controllers\Api\Compliance;

use App\Http\Controllers\Controller;
use App\Models\Compliance\CustomerRiskProfile;
use App\Services\Compliance\RiskScoringEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiskController extends Controller
{
    protected RiskScoringEngine $engine;

    public function __construct(RiskScoringEngine $engine)
    {
        $this->engine = $engine;
    }

    public function show(string $customerId): JsonResponse
    {
        $profile = CustomerRiskProfile::where('customer_id', (int) $customerId)
            ->with('customer')
            ->firstOrFail();
        return response()->json(['data' => $profile]);
    }

    public function history(string $customerId): JsonResponse
    {
        // Get current and previous scores from the profile
        $profile = CustomerRiskProfile::where('customer_id', (int) $customerId)->first();
        $history = [];
        if ($profile) {
            $history[] = [
                'score' => $profile->risk_score,
                'tier' => $profile->risk_tier,
                'changed_at' => $profile->score_changed_at?->toIso8601String(),
                'reason' => 'Current',
            ];
            if ($profile->previous_score) {
                $history[] = [
                    'score' => $profile->previous_score,
                    'tier' => CustomerRiskProfile::getTierForScore($profile->previous_score),
                    'changed_at' => $profile->score_changed_at?->subSecond()->toIso8601String(),
                    'reason' => 'Previous',
                ];
            }
        }
        return response()->json(['data' => $history]);
    }

    public function recalculate(string $customerId): JsonResponse
    {
        $profile = $this->engine->recalculateForCustomer((int) $customerId);
        return response()->json(['data' => $profile]);
    }

    public function lock(Request $request, string $customerId): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string|max:500']);
        $profile = CustomerRiskProfile::where('customer_id', (int) $customerId)->firstOrFail();
        $profile->lock(auth()->id(), $validated['reason']);
        return response()->json(['data' => $profile]);
    }

    public function unlock(string $customerId): JsonResponse
    {
        $profile = CustomerRiskProfile::where('customer_id', (int) $customerId)->firstOrFail();
        $profile->unlock();
        return response()->json(['data' => $profile]);
    }

    public function portfolio(): JsonResponse
    {
        $all = DB::select('SELECT risk_tier, COUNT(*) as count FROM customer_risk_profiles GROUP BY risk_tier');
        $byTier = [];
        foreach ($all as $row) {
            $byTier[$row->risk_tier] = (int) $row->count;
        }

        return response()->json([
            'data' => [
                'total' => (int) DB::table('customer_risk_profiles')->count(),
                'by_tier' => $byTier,
            ],
        ]);
    }
}
