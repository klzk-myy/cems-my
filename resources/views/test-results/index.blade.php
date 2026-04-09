@extends('layouts.app')

@section('title', 'Test Results - CEMS-MY')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1>Test Results</h1>
        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('test-results.statistics') }}" class="btn btn-primary">Statistics</a>
            <form action="{{ route('test-results.run') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-success">Run Full Suite</button>
            </form>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid" style="margin-bottom: 1.5rem;">
        <div class="card" style="background: linear-gradient(135deg, #38a169 0%, #2f855a 100%); color: white;">
            <h3 style="color: white; margin-bottom: 0.5rem;">Pass Rate (30 days)</h3>
            <div style="font-size: 2rem; font-weight: bold;">{{ number_format($statistics['avg_pass_rate'], 1) }}%</div>
            <div style="opacity: 0.8; font-size: 0.875rem;">
                Trend: {{ ucfirst($statistics['trend']) }}
            </div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, #3182ce 0%, #2c5282 100%); color: white;">
            <h3 style="color: white; margin-bottom: 0.5rem;">Total Runs</h3>
            <div style="font-size: 2rem; font-weight: bold;">{{ $statistics['total_runs'] }}</div>
            <div style="opacity: 0.8; font-size: 0.875rem;">
                Passed: {{ $statistics['passed_runs'] }} | Failed: {{ $statistics['failed_runs'] }}
            </div>
        </div>

        <div class="card" style="background: linear-gradient(135deg, #805ad5 0%, #6b46c1 100%); color: white;">
            <h3 style="color: white; margin-bottom: 0.5rem;">Avg Duration</h3>
            <div style="font-size: 2rem; font-weight: bold;">{{ number_format($statistics['avg_duration'], 1) }}s</div>
            <div style="opacity: 0.8; font-size: 0.875rem;">
                Per test run
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div style="background: #f7fafc; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <form action="{{ route('test-results.index') }}" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem; color: #4a5568;">Status</label>
                <select name="status" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px; min-width: 120px;">
                    <option value="all">All</option>
                    <option value="passed" {{ request('status') === 'passed' ? 'selected' : '' }}>Passed</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem; color: #4a5568;">Suite</label>
                <select name="suite" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px; min-width: 150px;">
                    <option value="all">All Suites</option>
                    @foreach($suites as $suite)
                        <option value="{{ $suite }}" {{ request('suite') === $suite ? 'selected' : '' }}>
                            {{ ucfirst($suite) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('test-results.index') }}" class="btn" style="background: #e2e8f0; color: #4a5568;">Clear</a>
        </form>
    </div>

    {{-- Test Runs Table --}}
    <table>
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
                        <code style="font-size: 0.75rem; background: #edf2f7; padding: 0.125rem 0.375rem; border-radius: 3px;">
                            {{ Str::limit($run->run_id, 8, '') }}
                        </code>
                    </td>
                    <td>
                        <span class="badge" style="background: #e2e8f0; color: #2d3748; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                            {{ ucfirst($run->test_suite) }}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge {{ $run->status_badge_class }}">
                            {{ $run->status_label }}
                        </span>
                    </td>
                    <td>
                        <div style="font-size: 0.875rem;">
                            <span style="color: #38a169; font-weight: 600;">{{ $run->passed }}</span>
                            <span style="color: #718096;">/</span>
                            <span style="color: #2d3748; font-weight: 600;">{{ $run->total_tests }}</span>
                        </div>
                        @if($run->failed > 0)
                            <div style="font-size: 0.75rem; color: #e53e3e;">
                                {{ $run->failed }} failed
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 60px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                                <div style="width: {{ $run->pass_rate }}%; height: 100%; background: {{ $run->pass_rate >= 90 ? '#38a169' : ($run->pass_rate >= 70 ? '#dd6b20' : '#e53e3e') }}; border-radius: 3px;"></div>
                            </div>
                            <span style="font-size: 0.875rem; font-weight: 600;">{{ number_format($run->pass_rate, 1) }}%</span>
                        </div>
                    </td>
                    <td>{{ $run->formatted_duration }}</td>
                    <td>
                        @if($run->git_branch)
                            <div style="font-size: 0.75rem;">
                                <span style="color: #805ad5; font-weight: 600;">{{ $run->git_branch }}</span>
                                @if($run->git_commit)
                                    <span style="color: #a0aec0;">@ {{ Str::limit($run->git_commit, 7) }}</span>
                                @endif
                            </div>
                        @else
                            <span style="color: #a0aec0; font-size: 0.75rem;">-</span>
                        @endif
                    </td>
                    <td>{{ $run->executed_by ?? 'System' }}</td>
                    <td>
                        <div style="font-size: 0.875rem; color: #2d3748;">
                            {{ $run->created_at->format('Y-m-d H:i') }}
                        </div>
                        <div style="font-size: 0.75rem; color: #718096;">
                            {{ $run->created_at->diffForHumans() }}
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('test-results.show', $run) }}" class="btn btn-sm btn-primary">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 2rem; color: #718096;">
                        No test runs found. 
                        <form action="{{ route('test-results.run') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm" style="margin-left: 0.5rem;">Run tests now</button>
                        </form>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    <div style="margin-top: 1rem;">
        {{ $testRuns->links() }}
    </div>
</div>
@endsection
