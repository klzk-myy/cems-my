@extends('layouts.app')

@section('title', 'Trial Balance - CEMS-MY')

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Trial Balance</h1>
    </div>
    <div class="page-header__actions">
        <form method="GET" class="flex items-center gap-2">
            <label for="as_of" class="text-sm text-gray-600">As of:</label>
            <input type="date" id="as_of" name="as_of" value="{{ $asOfDate }}" class="form-input" style="width: auto;">
            <button type="submit" class="btn btn--secondary btn--sm">Update</button>
        </form>
    </div>
</div>

<div class="card mb-6">
    <div class="flex gap-8 p-4 bg-gray-50 rounded-lg mb-6">
        <div class="flex flex-col">
            <span class="text-xs text-gray-500">Report Date:</span>
            <span class="font-semibold text-gray-800">{{ now()->format('Y-m-d H:i:s') }}</span>
        </div>
        <div class="flex flex-col">
            <span class="text-xs text-gray-500">As Of:</span>
            <span class="font-semibold text-gray-800">{{ $asOfDate }}</span>
        </div>
        <div class="flex flex-col">
            <span class="text-xs text-gray-500">Total Accounts:</span>
            <span class="font-semibold text-gray-800">{{ count($trialBalance['accounts']) }}</span>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Account Type</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trialBalance['accounts'] as $account)
            <tr>
                <td><strong class="text-gray-800">{{ $account['account_code'] }}</strong></td>
                <td>
                    <a href="{{ route('accounting.ledger.account', $account['account_code']) }}" class="text-blue-600 no-underline hover:underline">
                        {{ $account['account_name'] }}
                    </a>
                </td>
                <td class="text-gray-600">{{ $account['account_type'] }}</td>
                <td class="text-right text-gray-600">
                    {{ $account['debit'] != 0 ? 'RM ' . number_format($account['debit'], 2) : '-' }}
                </td>
                <td class="text-right text-gray-600">
                    {{ $account['credit'] != 0 ? 'RM ' . number_format($account['credit'], 2) : '-' }}
                </td>
                <td class="text-right font-semibold {{ $account['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    RM {{ number_format($account['balance'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-gray-200 bg-gray-50">
                <td colspan="3" class="text-right font-semibold text-gray-700">Total:</td>
                <td class="text-right font-semibold text-gray-700">RM {{ number_format($trialBalance['total_debits'], 2) }}</td>
                <td class="text-right font-semibold text-gray-700">RM {{ number_format($trialBalance['total_credits'], 2) }}</td>
                <td class="text-right font-semibold text-gray-700">RM {{ number_format((float) $trialBalance['total_balance'], 2) }}</td>
            </tr>
            <tr class="bg-gray-50">
                <td colspan="3" class="text-right font-semibold text-gray-700">Difference:</td>
                <td colspan="2" class="text-right font-semibold {{ abs((float) $trialBalance['total_debits'] - (float) $trialBalance['total_credits']) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                    @if(abs((float) $trialBalance['total_debits'] - (float) $trialBalance['total_credits']) < 0.01)
                        Balanced
                    @else
                        {{ number_format(abs((float) $trialBalance['total_debits'] - (float) $trialBalance['total_credits']), 2) }}
                    @endif
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Summary Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__value">RM {{ number_format($trialBalance['totals_by_type']['Asset'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Assets</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__value">RM {{ number_format(abs($trialBalance['totals_by_type']['Liability'] ?? 0), 2) }}</div>
        <div class="stat-card__label">Liabilities</div>
    </div>
    <div class="stat-card {{ ($trialBalance['totals_by_type']['Equity'] ?? 0) >= 0 ? 'stat-card--success' : 'stat-card--danger' }}">
        <div class="stat-card__value">RM {{ number_format($trialBalance['totals_by_type']['Equity'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Equity</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">RM {{ number_format($trialBalance['totals_by_type']['Revenue'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Revenue</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">RM {{ number_format(abs($trialBalance['totals_by_type']['Expense'] ?? 0), 2) }}</div>
        <div class="stat-card__label">Expenses</div>
    </div>
</div>
@endsection
