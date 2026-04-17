<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FindingController extends Controller
{
    protected string $apiBase = '/api/v1/compliance/findings';

    public function index(Request $request)
    {
        $params = array_filter([
            'status' => $request->get('status'),
            'severity' => $request->get('severity'),
            'type' => $request->get('type'),
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
        ]);

        $url = config('app.url').$this->apiBase;
        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        $response = Http::withToken(session('api_token'))->get($url);

        $data = $response->successful() ? $response->json() : [];

        $findings = $data['data'] ?? [];

        $statsResponse = Http::withToken(session('api_token'))
            ->get(config('app.url').$this->apiBase.'/stats');
        $stats = $statsResponse->successful() ? $statsResponse->json()['data'] ?? [] : [];

        $pagination = [
            'current_page' => $data['current_page'] ?? 1,
            'last_page' => $data['last_page'] ?? 1,
            'per_page' => $data['per_page'] ?? 25,
            'total' => $data['total'] ?? 0,
        ];

        return view('compliance.findings.index', compact('findings', 'stats', 'pagination'));
    }

    public function show(int $id)
    {
        $response = Http::withToken(session('api_token'))
            ->get(config('app.url').$this->apiBase.'/'.$id);

        if (! $response->successful()) {
            return redirect()->route('compliance.findings.index')
                ->with('error', 'Finding not found');
        }

        $finding = $response->json()['data'] ?? [];

        return view('compliance.findings.show', compact('finding'));
    }

    public function dismiss(Request $request, int $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $response = Http::withToken(session('api_token'))
            ->post(config('app.url').$this->apiBase.'/'.$id.'/dismiss', [
                'reason' => $validated['reason'],
            ]);

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Finding dismissed');
        }

        return redirect()->back()->with('error', $response->json()['message'] ?? 'Failed to dismiss finding');
    }
}
