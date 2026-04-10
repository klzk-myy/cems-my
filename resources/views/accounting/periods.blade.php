@extends('layouts.app')

@section('title', 'Accounting Periods - CEMS-MY')

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
            <span class="breadcrumbs__text">Periods</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="accounting-header">
    <h2>Accounting Periods</h2>
    <p>Manage accounting periods for financial reporting</p>
</div>

<div class="card">
    <h2>Period List</h2>

    @if($periods->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Period Code</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($periods as $period)
            <tr>
                <td><strong>{{ $period->period_code }}</strong></td>
                <td>{{ $period->start_date->format('Y-m-d') }}</td>
                <td>{{ $period->end_date->format('Y-m-d') }}</td>
                <td>
                    @if($period->is_closed)
                        <span class="status-badge status-inactive">Closed</span>
                    @elseif($period->is_current)
                        <span class="status-badge status-active">Current</span>
                    @else
                        <span class="status-badge status-pending">Open</span>
                    @endif
                </td>
                <td>
                    @if(!$period->is_closed)
                        <form action="{{ route('accounting.period.close', $period) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Close this period? This cannot be undone.')">Close Period</button>
                        </form>
                    @else
                        <span class="status-inactive">Closed</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 1rem;">
        {{ $periods->links() }}
    </div>
    @else
    <div class="alert alert-info">
        No accounting periods found. Periods are created automatically.
    </div>
    @endif
</div>

<div class="card">
    <h2>Period Management</h2>
    <div class="alert alert-info">
        <strong>Info:</strong> Accounting periods are typically monthly. The current period is automatically created if it doesn't exist.
        Closing a period locks all journal entries within that period.
    </div>
</div>
@endsection
