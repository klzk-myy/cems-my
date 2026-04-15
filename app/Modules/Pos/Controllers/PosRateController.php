<?php

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Models\PosDailyRate;
use App\Modules\Pos\Requests\PosRateRequest;
use App\Modules\Pos\Services\PosRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PosRateController extends Controller
{
    protected PosRateService $rateService;

    public function __construct(PosRateService $rateService)
    {
        $this->rateService = $rateService;
    }

    public function index(): View
    {
        $todayRates = $this->rateService->getTodayRates();
        $rateHistory = $this->rateService->getRateHistory(7);

        return view('pos.rates.index', [
            'todayRates' => $todayRates,
            'rateHistory' => $rateHistory,
        ]);
    }

    public function getTodayRates(): JsonResponse
    {
        $rates = $this->rateService->getTodayRates();

        $latestRate = PosDailyRate::forDate(today())
            ->active()
            ->latest('updated_at')
            ->with('creator')
            ->first();

        return response()->json([
            'date' => today()->toDateString(),
            'rates' => $rates ?? [],
            'last_updated' => $latestRate?->updated_at?->toIso8601String(),
            'updated_by' => $latestRate?->creator ? [
                'id' => $latestRate->creator->id,
                'name' => $latestRate->creator->name,
            ] : null,
        ]);
    }

    public function setDailyRates(PosRateRequest $request): JsonResponse
    {
        $success = $this->rateService->setDailyRates(
            $request->input('rates'),
            $request->user()->id
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Daily rates updated successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update daily rates',
        ], 500);
    }

    public function copyYesterdayRates(): JsonResponse
    {
        $previousRates = $this->rateService->copyPreviousDayRates();

        if ($previousRates === null) {
            return response()->json([
                'success' => false,
                'message' => 'No rates found for yesterday',
            ], 404);
        }

        $success = $this->rateService->setDailyRates(
            $previousRates,
            auth()->id()
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Previous day rates copied successfully',
                'rates' => $previousRates,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to copy previous day rates',
        ], 500);
    }

    public function getRateHistory(): JsonResponse
    {
        $days = request()->input('days', 7);
        $history = $this->rateService->getRateHistory($days);

        return response()->json([
            'history' => $history,
        ]);
    }
}
