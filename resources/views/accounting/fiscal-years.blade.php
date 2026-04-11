@extends('layouts.app')

@section('title', 'Fiscal Year Management - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item">
            <a href="{{ route('accounting.index') }}" class="breadcrumbs__link">Accounting</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Fiscal Years</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Fiscal Year Management</h1>
        <p class="page-header__subtitle">Manage fiscal years and year-end closing procedures</p>
    </div>
    @if(auth()->user()->role === 'admin' || auth()->user()->role === 'manager')
    <div class="page-header__actions">
        <button type="button" class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#createFiscalYearModal">
            Create Fiscal Year
        </button>
    </div>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success mb-6">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="alert alert-danger mb-6">{{ session('error') }}</div>
@endif

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $fiscalYears->total() }}</div>
        <div class="stat-card__label">Total Fiscal Years</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $fiscalYears->where('status', 'Open')->count() }}</div>
        <div class="stat-card__label">Open</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $fiscalYears->where('status', 'Closed')->count() }}</div>
        <div class="stat-card__label">Closed</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Fiscal Years</h3>
    </div>
    <div class="card-body p-0">
        @if($fiscalYears->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Year Code</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Closed By</th>
                    <th>Closed At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fiscalYears as $year)
                <tr>
                    <td><strong>{{ $year->year_code }}</strong></td>
                    <td>{{ $year->start_date->format('Y-m-d') }}</td>
                    <td>{{ $year->end_date->format('Y-m-d') }}</td>
                    <td>
                        @if($year->status === 'Open')
                            <span class="status-badge status-badge--active">Open</span>
                        @elseif($year->status === 'Closed')
                            <span class="status-badge status-badge--warning">Closed</span>
                        @else
                            <span class="status-badge status-badge--inactive">{{ $year->status }}</span>
                        @endif
                    </td>
                    <td>{{ $year->closedBy?->username ?? '-' }}</td>
                    <td>{{ $year->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ route('accounting.fiscal-years.report', $year->year_code) }}" class="btn btn--primary btn--sm">Report</a>
                            @if($year->isOpen() && auth()->user()->role === 'admin')
                                <button type="button" class="btn btn--danger btn--sm" data-bs-toggle="modal" data-bs-target="#closeYearModal{{ $year->id }}">
                                    Close Year
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>

                <!-- Close Year Modal -->
                @if($year->isOpen())
                <tr>
                    <td colspan="7">
                        <div class="modal fade" id="closeYearModal{{ $year->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Close Fiscal Year {{ $year->year_code }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="{{ route('accounting.fiscal-years.close', $year) }}" method="POST">
                                        @csrf
                                        <div class="modal-body">
                                            <p>Are you sure you want to close fiscal year <strong>{{ $year->year_code }}</strong>?</p>
                                            <p class="text-muted">This will:</p>
                                            <ol>
                                                <li>Close all revenue accounts to Income Summary</li>
                                                <li>Close all expense accounts to Income Summary</li>
                                                <li>Transfer net income to Retained Earnings</li>
                                            </ol>
                                            <p class="text-warning">This action cannot be undone.</p>

                                            <div class="mb-3">
                                                <label for="confirm_code" class="form-label">Type the year code to confirm:</label>
                                                <input type="text" name="confirm_code" id="confirm_code" class="form-input" placeholder="{{ $year->year_code }}" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn--danger">Close Fiscal Year</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-12 text-center">
            <div class="text-5xl mb-4 text-gray-300">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Fiscal Years</h3>
            <p class="text-gray-500">No fiscal years have been created yet.</p>
        </div>
        @endif
    </div>
    @if($fiscalYears->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $fiscalYears->links() }}
    </div>
    @endif
</div>

<!-- Year-End Report -->
@if(isset($yearReport))
<div class="card mt-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold">Year-End Report: {{ $yearReport['fiscal_year']->year_code }}</h3>
        <p class="text-muted mb-0">As of {{ $yearReport['as_of_date'] }}</p>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-700 mb-3">Summary</h4>
                <table class="data-table">
                    <tbody>
                        <tr>
                            <td>Total Revenue</td>
                            <td class="text-right">RM {{ number_format((float) $yearReport['profit_and_loss']['total_revenue'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Expenses</td>
                            <td class="text-right">( RM {{ number_format((float) $yearReport['profit_and_loss']['total_expenses'], 2) }} )</td>
                        </tr>
                        <tr class="border-t-2">
                            <td class="font-semibold">Net Income</td>
                            <td class="text-right font-semibold {{ (float) $yearReport['net_income'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                RM {{ number_format((float) $yearReport['net_income'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div>
                <h4 class="font-semibold text-gray-700 mb-3">Trial Balance Summary</h4>
                <table class="data-table">
                    <tbody>
                        <tr>
                            <td>Total Debits</td>
                            <td class="text-right">RM {{ number_format((float) $yearReport['trial_balance']['total_debits'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Credits</td>
                            <td class="text-right">RM {{ number_format((float) $yearReport['trial_balance']['total_credits'], 2) }}</td>
                        </tr>
                        <tr class="border-t-2">
                            <td colspan="2" class="text-center {{ abs((float) $yearReport['trial_balance']['total_debits'] - (float) $yearReport['trial_balance']['total_credits']) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                                {{ abs((float) $yearReport['trial_balance']['total_debits'] - (float) $yearReport['trial_balance']['total_credits']) < 0.01 ? 'Balanced' : 'Out of Balance' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Create Fiscal Year Modal -->
<div class="modal fade" id="createFiscalYearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Fiscal Year</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('accounting.fiscal-years.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-4">
                        <label for="year_code" class="form-label">Year Code</label>
                        <input type="text" name="year_code" id="year_code" class="form-input" placeholder="FY2026" required>
                    </div>
                    <div class="mb-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-input" required>
                    </div>
                    <div class="mb-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-input" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection