@extends('layouts.app')

@section('title', 'Counter History - CEMS-MY')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Counter History - {{ $counter->code }}</h1>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('counters.history', $counter) }}" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="user_id" class="form-label">User</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sessions Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Opened At</th>
                            <th>Closed At</th>
                            <th>Status</th>
                            <th>Total Variance (MYR)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                            <tr>
                                <td>{{ $session->session_date->format('Y-m-d') }}</td>
                                <td>{{ $session->user->name }}</td>
                                <td>{{ $session->opened_at->format('H:i:s') }}</td>
                                <td>{{ $session->closed_at ? $session->closed_at->format('H:i:s') : '-' }}</td>
                                <td>
                                    @if($session->status === 'open')
                                        <span class="badge bg-success">Open</span>
                                    @elseif($session->status === 'closed')
                                        <span class="badge bg-secondary">Closed</span>
                                    @else
                                        <span class="badge bg-warning">Handed Over</span>
                                    @endif
                                </td>
                                <td>RM {{ number_format(0.00, 2) }}</td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">View Details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No sessions found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $sessions->links() }}
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('counters.index') }}" class="btn btn-secondary">Back to Counters</a>
    </div>
</div>
@endsection
