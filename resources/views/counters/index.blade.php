@extends('layouts.app')

@section('title', 'Counter Management - CEMS-MY')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">Counter Management</h1>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Counters</h5>
                    <p class="card-text display-4">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Open Counters</h5>
                    <p class="card-text display-4 text-success">{{ $stats['open'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Available Counters</h5>
                    <p class="card-text display-4 text-primary">{{ $stats['available'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Counters Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Counters</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Current User</th>
                            <th>Session Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($counters as $counter)
                        <tr>
                            <td>{{ $counter->code }}</td>
                            <td>{{ $counter->name }}</td>
                            <td>
                                @if($counter->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if($counter->sessions->count() > 0)
                                    {{ $counter->sessions->first()->user->name }}
                                @else
                                    <em>None</em>
                                @endif
                            </td>
                            <td>
                                @if($counter->sessions->count() > 0)
                                    {{ $counter->sessions->first()->opened_at->format('H:i') }}
                                @else
                                    <em>-</em>
                                @endif
                            </td>
                            <td>
                                @if($counter->sessions->count() > 0)
                                    <a href="{{ route('counters.close.show', $counter) }}" class="btn btn-sm btn-warning">Close</a>
                                    <a href="{{ route('counters.handover.show', $counter) }}" class="btn btn-sm btn-info">Handover</a>
                                @else
                                    <a href="{{ route('counters.open.show', $counter) }}" class="btn btn-sm btn-primary">Open</a>
                                @endif
                                <a href="{{ route('counters.history', $counter) }}" class="btn btn-sm btn-secondary">History</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
