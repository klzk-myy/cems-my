@extends('layouts.app')

@section('title', 'Test Statistics - CEMS-MY')

@section('content')
<div class="card">
    <h1>Test Statistics</h1>
    
    <div style="margin-bottom: 1.5rem;">
        <form action="{{ route('test-results.statistics') }}" method="GET" style="display: flex; gap: 1rem; align-items: center;">
            <label style="font-weight: 600;">Period:</label>
            <select name="days" onchange="this.form.submit()" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px;">
                <option value="7" {{ request('days', 30) == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ request('days', 30) == 90 ? 'selected' : '' }}>Last 90 days</option>
            </select>
        </form>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid" style="margin-bottom: 2rem;">
        <div class="card" style="text-align: center; border-top: 4px solid #38a169;">
            <div style="font-size: 3rem; font-weight: bold; color: #38a169;">{{ number_format($statistics['avg_pass_rate'], 1) }}%</div>
            <div style="color: #718096;">Average Pass Rate</div>
        </div>
        <div class="card" style="text-align: center; border-top: 4px solid #3182ce;">
            <div style="font-size: 3rem; font-weight: bold; color: #3182ce;">{{ $statistics['total_runs'] }}</div>
            <div style="color: #718096;">Total Test Runs</div>
        </div>
        <div class="card" style="text-align: center; border-top: 4px solid #805ad5;">
            <div style="font-size: 3rem; font-weight: bold; color: #805ad5;">{{ $statistics['passed_runs'] }}</div>
            <div style="color: #718096;">Passed Runs</div>
        </div>
        <div class="card" style="text-align: center; border-top: 4px solid #e53e3e;">
            <div style="font-size: 3rem; font-weight: bold; color: #e53e3e;">{{ $statistics['failed_runs'] }}</div>
            <div style="color: #718096;">Failed Runs</div>
        </div>
    </div>

    {{-- Trend Chart --}}
    <div class="card" style="margin-bottom: 2rem;">
        <h2>Pass Rate Trend</h2>
        <div style="height: 300px; background: #f7fafc; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
            @if($trendData->isNotEmpty())
                <canvas id="trendChart" style="width: 100%; height: 100%;"></canvas>
            @else
                <p style="color: #718096;">No data available for the selected period</p>
            @endif
        </div>
    </div>

    {{-- Latest by Suite --}}
    <div class="card">
        <h2>Latest Run by Suite</h2>
        <table>
            <thead>
                <tr>
                    <th>Suite</th>
                    <th>Status</th>
                    <th>Pass Rate</th>
                    <th>Tests</th>
                    <th>Duration</th>
                    <th>Last Run</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($latestBySuite as $suite => $result)
                    @if($result)
                        <tr>
                            <td>{{ ucfirst($suite) }}</td>
                            <td>
                                <span class="status-badge {{ $result->status_badge_class }}">
                                    {{ $result->status_label }}
                                </span>
                            </td>
                            <td>{{ number_format($result->pass_rate, 1) }}%</td>
                            <td>{{ $result->passed }}/{{ $result->total_tests }}</td>
                            <td>{{ $result->formatted_duration }}</td>
                            <td>{{ $result->created_at->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('test-results.show', $result) }}" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ ucfirst($suite) }}</td>
                            <td colspan="6" style="color: #a0aec0; text-align: center;">No runs yet</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($trendData->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($trendData->pluck('date')) !!},
            datasets: [{
                label: 'Pass Rate (%)',
                data: {!! json_encode($trendData->pluck('avg_pass_rate')) !!},
                borderColor: '#38a169',
                backgroundColor: 'rgba(56, 161, 105, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Failed Runs',
                data: {!! json_encode($trendData->pluck('failed_count')) !!},
                borderColor: '#e53e3e',
                backgroundColor: 'rgba(229, 62, 62, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Pass Rate (%)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Failed Count'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
</script>
@endif
@endsection
