@extends('layouts.app')

@section('title', 'Test Results - CEMS-MY')

@section('breadcrumbs')
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="{{ route('dashboard') }}" class="breadcrumbs__link">Dashboard</a>
            <svg class="breadcrumbs__separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">
            <span class="breadcrumbs__text">Test Results</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__content">
        <h1 class="page-header__title">Test Results</h1>
        <p class="page-header__subtitle">View and manage test run results</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('test-results.statistics') }}" class="btn btn--secondary btn--sm">Statistics</a>
        <form action="{{ route('test-results.run') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="btn btn--success btn--sm">Run Full Suite</button>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-6">
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ number_format($statistics['avg_pass_rate'], 1) }}%</div>
        <div class="stat-card__label">Pass Rate (30 days)</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $statistics['total_runs'] }}</div>
        <div class="stat-card__label">Total Runs</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ number_format($statistics['avg_duration'], 1) }}s</div>
        <div class="stat-card__label">Avg Duration</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $statistics['passed_runs'] }} / {{ $statistics['failed_runs'] }}</div>
        <div class="stat-card__label">Passed / Failed</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-800">Filter Test Runs</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="all">All</option>
                    <option value="passed" {{ request('status') === 'passed' ? 'selected' : '' }}>Passed</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
                </select>
            </div>
            <div>
                <label for="suite" class="form-label">Suite</label>
                <select name="suite" id="suite" class="form-select">
                    <option value="all">All Suites</option>
                    @foreach($suites as $suite)
                        <option value="{{ $suite }}" {{ request('suite') === $suite ? 'selected' : '' }}>
                            {{ ucfirst($suite) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn--primary">Filter</button>
            <a href="{{ route('test-results.index') }}" class="btn btn--secondary">Clear</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Run ID</th>
                    <th>Suite</th>
                    <th>Status</th>
                    <th>Tests</th>
                    <th>Pass Rate</th>
                    <th>Duration</th>
                    <th>Git</th>
                    <th>Executed By</th>
                    <th>Timestamp</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($testRuns as $run)
                    <tr>
                        <td>
                            <code class="font-mono bg-gray-100 px-2 py-1 rounded text-sm">{{ Str::limit($run->run_id, 8, '') }}</code>
                        </td>
                        <td>
                            <span class="status-badge status-badge--default">
                                {{ ucfirst($run->test_suite) }}
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-badge--{{ strtolower($run->status_label) }}">
                                {{ $run->status_label }}
                            </span>
                        </td>
                        <td>
                            <div>
                                <span class="text-green-600 font-semibold">{{ $run->passed }}</span>
                                <span class="text-gray-500">/</span>
                                <span class="font-semibold">{{ $run->total_tests }}</span>
                            </div>
                            @if($run->failed > 0)
                                <div class="text-sm text-red-600">
                                    {{ $run->failed }} failed
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div style="width: 60px; height: 6px; background: #e2e8f0; border-radius: 4px;">
                                    <div style="width: {{ $run->pass_rate }}%; height: 100%; border-radius: 4px; background: {{ $run->pass_rate >= 90 ? '#38a169' : ($run->pass_rate >= 70 ? '#dd6b20' : '#e53e3e') }};"></div>
                                </div>
                                <span class="font-semibold">{{ number_format($run->pass_rate, 1) }}%</span>
                            </div>
                        </td>
                        <td>{{ $run->formatted_duration }}</td>
                        <td>
                            @if($run->git_branch)
                                <div class="text-sm">
                                    <span class="font-semibold text-purple-600">{{ $run->git_branch }}</span>
                                    @if($run->git_commit)
                                        <span class="text-gray-500">@ {{ Str::limit($run->git_commit, 7) }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-500 text-sm">-</span>
                            @endif
                        </td>
                        <td>{{ $run->executed_by ?? 'System' }}</td>
                        <td>
                            <div>{{ $run->created_at->format('Y-m-d H:i') }}</div>
                            <div class="text-sm text-gray-500">{{ $run->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <a href="{{ route('test-results.show', $run) }}" class="btn btn--primary btn--sm">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-gray-500">
                            No test runs found.
                            <form action="{{ route('test-results.run') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn--success btn--sm ml-2">Run tests now</button>
                            </form>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($testRuns->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $testRuns->links() }}
    </div>
    @endif
</div>
@endsection
