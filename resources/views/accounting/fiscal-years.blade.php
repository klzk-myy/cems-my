@extends('layouts.app')

@section('title', 'Fiscal Year Management - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Fiscal Year Management</h2>
    <p>Manage fiscal years and year-end closing procedures</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Fiscal Years</h4>
        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'manager')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFiscalYearModal">
            Create Fiscal Year
        </button>
        @endif
    </div>
    <div class="card-body">
        <table class="table table-striped">
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
                @forelse($fiscalYears as $year)
                <tr>
                    <td><strong>{{ $year->year_code }}</strong></td>
                    <td>{{ $year->start_date->format('Y-m-d') }}</td>
                    <td>{{ $year->end_date->format('Y-m-d') }}</td>
                    <td>
                        @if($year->status === 'Open')
                            <span class="badge bg-success">Open</span>
                        @elseif($year->status === 'Closed')
                            <span class="badge bg-warning">Closed</span>
                        @else
                            <span class="badge bg-secondary">{{ $year->status }}</span>
                        @endif
                    </td>
                    <td>{{ $year->closedBy?->username ?? '-' }}</td>
                    <td>{{ $year->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>
                        <a href="{{ route('accounting.fiscal-years.report', $year->year_code) }}" class="btn btn-sm btn-info">Report</a>
                        @if($year->isOpen() && auth()->user()->role === 'admin')
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#closeYearModal{{ $year->id }}">
                                Close Year
                            </button>
                        @endif
                    </td>
                </tr>

                <!-- Close Year Modal -->
                @if($year->isOpen())
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
                                        <input type="text" name="confirm_code" id="confirm_code" class="form-control" placeholder="{{ $year->year_code }}" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Close Fiscal Year</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
                @empty
                <tr>
                    <td colspan="7" class="text-muted">No fiscal years found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Year-End Report -->
@if(isset($yearReport))
<div class="card mt-4">
    <div class="card-header">
        <h4>Year-End Report: {{ $yearReport['fiscal_year']->year_code }}</h4>
        <p class="text-muted mb-0">As of {{ $yearReport['as_of_date'] }}</p>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Summary</h5>
                <table class="table">
                    <tr>
                        <td>Total Revenue</td>
                        <td style="text-align: right;">{{ number_format((float) $yearReport['profit_and_loss']['total_revenue'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total Expenses</td>
                        <td style="text-align: right;">( {{ number_format((float) $yearReport['profit_and_loss']['total_expenses'], 2) }} )</td>
                    </tr>
                    <tr class="table-{{ (float) $yearReport['net_income'] >= 0 ? 'success' : 'danger' }}">
                        <td><strong>Net Income</strong></td>
                        <td style="text-align: right;">
                            <strong>{{ number_format((float) $yearReport['net_income'], 2) }}</strong>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Trial Balance Summary</h5>
                <table class="table">
                    <tr>
                        <td>Total Debits</td>
                        <td style="text-align: right;">{{ number_format((float) $yearReport['trial_balance']['total_debits'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total Credits</td>
                        <td style="text-align: right;">{{ number_format((float) $yearReport['trial_balance']['total_credits'], 2) }}</td>
                    </tr>
                    <tr class="{{ abs((float) $yearReport['trial_balance']['total_debits'] - (float) $yearReport['trial_balance']['total_credits']) < 0.01 ? 'table-success' : 'table-danger' }}">
                        <td colspan="2" class="text-center">
                            {{ abs((float) $yearReport['trial_balance']['total_debits'] - (float) $yearReport['trial_balance']['total_credits']) < 0.01 ? 'Balanced' : 'Out of Balance' }}
                        </td>
                    </tr>
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
                    <div class="mb-3">
                        <label for="year_code" class="form-label">Year Code</label>
                        <input type="text" name="year_code" id="year_code" class="form-control" placeholder="FY2026" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
