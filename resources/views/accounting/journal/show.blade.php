@extends('layouts.app')

@section('title', 'Journal Entry #' . $entry->id . ' - CEMS-MY')

@section('content')
<div class="journal-header">
    <h2>Journal Entry #{{ $entry->id }}</h2>
    <div class="header-actions">
        @if($entry->isPosted() && !$entry->isReversed())
            <form method="POST" action="{{ route('accounting.journal.reverse', $entry) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to reverse this entry?');">
                @csrf
                <input type="hidden" name="reason" value="Manual reversal">
                <button type="submit" class="btn btn-warning">Reverse Entry</button>
            </form>
        @endif
        <a href="{{ route('accounting.journal') }}" class="btn btn-secondary">Back to Journal</a>
    </div>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));">
    <!-- Entry Details -->
    <div class="card">
        <h2>Entry Details</h2>
        <table class="detail-table">
            <tr>
                <th>Entry ID</th>
                <td>#{{ $entry->id }}</td>
            </tr>
            <tr>
                <th>Date</th>
                <td>{{ $entry->entry_date->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="status-badge status-{{ strtolower($entry->status) }}">
                        {{ $entry->status }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Description</th>
                <td>{{ $entry->description }}</td>
            </tr>
            <tr>
                <th>Reference</th>
                <td>{{ $entry->reference_type }} {{ $entry->reference_id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Posted By</th>
                <td>{{ $entry->postedBy->username ?? 'System' }}</td>
            </tr>
            <tr>
                <th>Posted At</th>
                <td>{{ $entry->posted_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            @if($entry->isReversed())
                <tr>
                    <th>Reversed By</th>
                    <td>{{ $entry->reversedBy->username ?? 'System' }}</td>
                </tr>
                <tr>
                    <th>Reversed At</th>
                    <td>{{ $entry->reversed_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @endif
        </table>
    </div>
    
    <!-- Balance Summary -->
    <div class="card">
        <h2>Balance Summary</h2>
        <table class="detail-table">
            <tr>
                <th>Total Debits</th>
                <td class="currency">RM {{ number_format($entry->getTotalDebits(), 2) }}</td>
            </tr>
            <tr>
                <th>Total Credits</th>
                <td class="currency">RM {{ number_format($entry->getTotalCredits(), 2) }}</td>
            </tr>
            <tr>
                <th>Difference</th>
                <td class="currency {{ $entry->isBalanced() ? 'text-success' : 'text-danger' }}">
                    RM {{ number_format(abs($entry->getTotalDebits() - $entry->getTotalCredits()), 2) }}
                </td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    @if($entry->isBalanced())
                        <span style="color: #38a169;">✓ Balanced</span>
                    @else
                        <span style="color: #e53e3e;">✗ Not Balanced</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- Journal Lines -->
<div class="card">
    <h2>Journal Lines</h2>
    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entry->lines as $line)
            <tr>
                <td><strong>{{ $line->account_code }}</strong></td>
                <td>{{ $line->account->account_name ?? 'N/A' }}</td>
                <td>{{ $line->description ?: '-' }}</td>
                <td class="text-right {{ $line->debit > 0 ? 'debit' : '' }}">
                    {{ $line->debit > 0 ? 'RM ' . number_format($line->debit, 2) : '-' }}
                </td>
                <td class="text-right {{ $line->credit > 0 ? 'credit' : '' }}">
                    {{ $line->credit > 0 ? 'RM ' . number_format($line->credit, 2) : '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Total:</th>
                <th class="text-right">RM {{ number_format($entry->getTotalDebits(), 2) }}</th>
                <th class="text-right">RM {{ number_format($entry->getTotalCredits(), 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>

@section('styles')
<style>
    .journal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .header-actions {
        display: flex;
        gap: 0.5rem;
    }
    .detail-table {
        width: 100%;
    }
    .detail-table th,
    .detail-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .detail-table th {
        width: 35%;
        text-align: left;
        color: #718096;
        font-weight: 500;
    }
    .text-right {
        text-align: right;
    }
    .text-success {
        color: #38a169;
    }
    .text-danger {
        color: #e53e3e;
    }
    .debit {
        color: #3182ce;
    }
    .credit {
        color: #38a169;
    }
    tfoot tr {
        border-top: 2px solid #e2e8f0;
        background: #f7fafc;
    }
</style>
@endsection
@endsection
