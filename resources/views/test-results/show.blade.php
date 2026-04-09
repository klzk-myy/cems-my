@extends('layouts.app')

@section('title', 'Test Run Details - CEMS-MY')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1>Test Run Details</h1>
            <p style="color: #718096; margin-top: 0.25rem;">
                Run ID: <code>{{ $testResult->run_id }}</code>
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('test-results.index') }}" class="btn">Back to List</a>
            @if($previousRun)
                <a href="{{ route('test-results.show', $previousRun) }}" class="btn btn-primary">Previous Run</a>
            @endif
        </div>
    </div>

    {{-- Status Banner --}}
    <div style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; 
        background: {{ $testResult->status === 'passed' ? '#c6f6d5' : ($testResult->status === 'failed' ? '#fed7d7' : '#ebf8ff') }};
        border-left: 4px solid {{ $testResult->status === 'passed' ? '#38a169' : ($testResult->status === 'failed' ? '#e53e3e' : '#3182ce') }};">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0; color: {{ $testResult->status === 'passed' ? '#276749' : ($testResult->status === 'failed' ? '#c53030' : '#2b6cb0') }};">
                    {{ $testResult->status_label }}
                </h2>
                <p style="margin: 0.25rem 0 0 0; color: #4a5568;">
                    {{ $testResult->total_tests }} tests with {{ $testResult->assertions }} assertions in {{ $testResult->formatted_duration }}
                </p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 2.5rem; font-weight: bold; color: {{ $testResult->status === 'passed' ? '#38a169' : ($testResult->status === 'failed' ? '#e53e3e' : '#3182ce') }};">
                    {{ number_format($testResult->pass_rate, 1) }}%
                </div>
                <div style="font-size: 0.875rem; color: #718096;">Pass Rate</div>
            </div>
        </div>
    </div>

    {{-- Metrics Grid --}}
    <div class="grid" style="margin-bottom: 1.5rem;">
        <div class="card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #38a169;">{{ $testResult->passed }}</div>
            <div style="color: #718096; font-size: 0.875rem;">Passed</div>
        </div>
        <div class="card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: {{ $testResult->failed > 0 ? '#e53e3e' : '#a0aec0' }};">{{ $testResult->failed }}</div>
            <div style="color: #718096; font-size: 0.875rem;">Failed</div>
        </div>
        <div class="card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #dd6b20;">{{ $testResult->skipped }}</div>
            <div style="color: #718096; font-size: 0.875rem;">Skipped</div>
        </div>
        <div class="card" style="text-align: center;">
            <div style="font-size: 2rem; font-weight: bold; color: #3182ce;">{{ $testResult->assertions }}</div>
            <div style="color: #718096; font-size: 0.875rem;">Assertions</div>
        </div>
    </div>

    {{-- Metadata --}}
    <div style="background: #f7fafc; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div>
                <div style="font-size: 0.75rem; color: #718096; text-transform: uppercase; font-weight: 600;">Test Suite</div>
                <div style="font-weight: 600; color: #2d3748;">{{ ucfirst($testResult->test_suite) }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #718096; text-transform: uppercase; font-weight: 600;">Duration</div>
                <div style="font-weight: 600; color: #2d3748;">{{ $testResult->formatted_duration }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #718096; text-transform: uppercase; font-weight: 600;">Executed By</div>
                <div style="font-weight: 600; color: #2d3748;">{{ $testResult->executed_by ?? 'System' }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #718096; text-transform: uppercase; font-weight: 600;">Started</div>
                <div style="font-weight: 600; color: #2d3748;">{{ $testResult->started_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #718096; text-transform: uppercase; font-weight: 600;">Completed</div>
                <div style="font-weight: 600; color: #2d3748;">{{ $testResult->completed_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</div>
            </div>
            @if($testResult->git_branch)
                <div>
                    <div style="font-size: 0.75rem; color: #718096; text-transform: uppercase; font-weight: 600;">Git Branch</div>
                    <div style="font-weight: 600; color: #805ad5;">{{ $testResult->git_branch }}</div>
                </div>
            @endif
            @if($testResult->git_commit)
                <div>
                    <div style="font-size: 0.75rem; color: #718096; text-transform: uppercase; font-weight: 600;">Git Commit</div>
                    <div style="font-weight: 600; color: #2d3748; font-family: monospace;">{{ Str::limit($testResult->git_commit, 12) }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Comparison with Previous Run --}}
    @if($previousRun)
        <div style="background: #ebf8ff; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #3182ce;">
            <h3 style="margin: 0 0 0.5rem 0; color: #2b6cb0;">Comparison with Previous Run</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                @php
                    $passRateDiff = $testResult->pass_rate - $previousRun->pass_rate;
                    $durationDiff = $testResult->duration - $previousRun->duration;
                @endphp
                <div>
                    <div style="font-size: 0.75rem; color: #718096;">Pass Rate Change</div>
                    <div style="font-weight: 600; {{ $passRateDiff >= 0 ? 'color: #38a169;' : 'color: #e53e3e;' }}">
                        {{ $passRateDiff >= 0 ? '+' : '' }}{{ number_format($passRateDiff, 1) }}%
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: #718096;">Duration Change</div>
                    <div style="font-weight: 600; {{ $durationDiff <= 0 ? 'color: #38a169;' : 'color: #e53e3e;' }}">
                        {{ $durationDiff >= 0 ? '+' : '' }}{{ number_format($durationDiff, 1) }}s
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Failures Section --}}
    @if(!empty($testResult->failures))
        <div class="card" style="border: 1px solid #fc8181; background: #fff5f5;">
            <h2 style="color: #c53030; margin-bottom: 1rem;">
                Failures ({{ count($testResult->failures) }})
            </h2>
            <div style="max-height: 400px; overflow-y: auto;">
                @foreach($testResult->failures as $index => $failure)
                    <div style="background: white; padding: 1rem; border-radius: 4px; margin-bottom: 0.5rem; border-left: 3px solid #e53e3e;">
                        <div style="font-weight: 600; color: #c53030; margin-bottom: 0.5rem;">
                            {{ $index + 1 }}. {{ $failure['test'] ?? 'Unknown Test' }}
                        </div>
                        @if(isset($failure['details']))
                            <pre style="background: #f7fafc; padding: 0.75rem; border-radius: 4px; font-size: 0.75rem; overflow-x: auto; margin: 0;"><code>{{ $failure['details'] }}</code></pre>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Errors Section --}}
    @if(!empty($testResult->errors))
        <div class="card" style="border: 1px solid #f6ad55; background: #fffaf0; margin-top: 1rem;">
            <h2 style="color: #c05621; margin-bottom: 1rem;">
                Errors ({{ count($testResult->errors) }})
            </h2>
            <div style="max-height: 300px; overflow-y: auto;">
                @foreach($testResult->errors as $error)
                    <div style="background: white; padding: 1rem; border-radius: 4px; margin-bottom: 0.5rem; border-left: 3px solid #dd6b20;">
                        <pre style="font-size: 0.75rem; margin: 0; color: #c05621;"><code>{{ $error }}</code></pre>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Full Output Toggle --}}
    <div class="card" style="margin-top: 1.5rem;">
        <h2>Full Test Output</h2>
        <details>
            <summary style="cursor: pointer; padding: 0.5rem; background: #f7fafc; border-radius: 4px; font-weight: 600;">
                Click to view full output
            </summary>
            <pre style="background: #1a202c; color: #e2e8f0; padding: 1rem; border-radius: 4px; overflow-x: auto; max-height: 500px; overflow-y: auto; margin-top: 0.5rem;"><code>{{ $testResult->output ?? 'No output available' }}</code></pre>
        </details>
    </div>
</div>
@endsection
