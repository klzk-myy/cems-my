<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    protected ReportingService $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    public function generateLCTR(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $filepath = $this->reportingService->generateLCTR($request->month);

        return response()->json([
            'message' => 'LCTR report generated',
            'filename' => basename($filepath),
            'download_url' => url('/reports/download/' . basename($filepath)),
        ]);
    }

    public function generateMSB2(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $filepath = $this->reportingService->generateMSB2($request->date);

        return response()->json([
            'message' => 'MSB(2) report generated',
            'filename' => basename($filepath),
            'download_url' => url('/reports/download/' . basename($filepath)),
        ]);
    }

    public function download(string $filename)
    {
        $filepath = "reports/{$filename}";

        if (!Storage::exists($filepath)) {
            abort(404, 'Report not found');
        }

        return Storage::download($filepath);
    }
}
