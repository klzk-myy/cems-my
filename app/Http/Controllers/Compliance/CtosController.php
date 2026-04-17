<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CtosController extends Controller
{
    protected string $apiBase = '/api/v1/ctos';

    public function index(Request $request)
    {
        $params = array_filter([
            'status' => $request->get('status'),
            'branch_id' => $request->get('branch_id'),
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
        ]);

        $url = config('app.url').$this->apiBase;
        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        $response = Http::withToken(session('api_token'))->get($url);

        $data = $response->successful() ? $response->json() : [];

        $reports = $data['data'] ?? [];
        $pagination = [
            'current_page' => $data['current_page'] ?? 1,
            'last_page' => $data['last_page'] ?? 1,
            'per_page' => $data['per_page'] ?? 25,
            'total' => $data['total'] ?? 0,
        ];

        $summary = [
            'total' => collect($reports)->count(),
            'draft' => collect($reports)->where('status', 'Draft')->count(),
            'submitted' => collect($reports)->where('status', 'Submitted')->count(),
            'acknowledged' => collect($reports)->where('status', 'Acknowledged')->count(),
            'rejected' => collect($reports)->where('status', 'Rejected')->count(),
        ];

        return view('compliance.ctos.index', compact('reports', 'pagination', 'summary'));
    }

    public function show(int $id)
    {
        $response = Http::withToken(session('api_token'))
            ->get(config('app.url').$this->apiBase.'/'.$id);

        if (! $response->successful()) {
            return redirect()->route('compliance.ctos.index')
                ->with('error', 'CTOS report not found');
        }

        $report = $response->json();

        return view('compliance.ctos.show', compact('report'));
    }

    public function submit(Request $request, int $id)
    {
        $response = Http::withToken(session('api_token'))
            ->post(config('app.url').$this->apiBase.'/'.$id.'/submit');

        if ($response->successful()) {
            return redirect()->back()->with('success', 'CTOS report submitted to BNM successfully');
        }

        return redirect()->back()->with('error', $response->json()['message'] ?? 'Failed to submit CTOS report');
    }
}
