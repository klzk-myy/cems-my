<?php

namespace App\Http\Controllers\Api\Compliance;

use App\Http\Controllers\Controller;
use App\Models\Compliance\ComplianceFinding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ComplianceFinding::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('severity')) {
            $query->where('severity', $request->input('severity'));
        }
        if ($request->has('type')) {
            $query->where('finding_type', $request->input('type'));
        }
        if ($request->has('date_from')) {
            $query->whereDate('generated_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('generated_at', '<=', $request->input('date_to'));
        }

        $findings = $query->orderBy('generated_at', 'desc')->paginate(20);

        return response()->json($findings);
    }

    public function show(int $id): JsonResponse
    {
        $finding = ComplianceFinding::with('subject')->findOrFail($id);

        return response()->json(['data' => $finding]);
    }

    public function dismiss(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $finding = ComplianceFinding::findOrFail($id);
        $finding->dismiss($request->input('reason'));

        return response()->json(['message' => 'Finding dismissed', 'data' => $finding]);
    }

    public function stats(): JsonResponse
    {
        $total = ComplianceFinding::count();
        $newCount = ComplianceFinding::new()->count();

        $bySeverity = ComplianceFinding::query()
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity');

        $byStatus = ComplianceFinding::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byType = ComplianceFinding::query()
            ->selectRaw('finding_type, count(*) as count')
            ->groupBy('finding_type')
            ->pluck('count', 'finding_type');

        return response()->json([
            'total' => $total,
            'new' => $newCount,
            'by_severity' => $bySeverity,
            'by_status' => $byStatus,
            'by_type' => $byType,
        ]);
    }
}
