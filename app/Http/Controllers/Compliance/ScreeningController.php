<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ScreeningController extends Controller
{
    protected string $apiBase = '/api/v1/screening';

    public function show(int $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $statusResponse = Http::withToken(session('api_token'))
            ->get(config('app.url').$this->apiBase.'/customer/'.$customerId.'/status');

        $status = $statusResponse->successful() ? $statusResponse->json()['data'] ?? [] : [];

        $historyResponse = Http::withToken(session('api_token'))
            ->get(config('app.url').$this->apiBase.'/customer/'.$customerId.'/history');

        $history = $historyResponse->successful() ? $historyResponse->json()['data'] ?? [] : [];

        return view('compliance.screening.show', [
            'customer' => $customer,
            'status' => $status,
            'history' => $history,
        ]);
    }

    public function screen(Request $request, int $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $response = Http::withToken(session('api_token'))
            ->post(config('app.url').$this->apiBase.'/customer/'.$customerId);

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Customer screened successfully');
        }

        return redirect()->back()->with('error', $response->json()['message'] ?? 'Failed to screen customer');
    }
}
