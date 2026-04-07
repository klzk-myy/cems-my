@extends('layouts.app')

@section('title', 'Journal Entry Workflow - CEMS-MY')

@section('content')
<div class="accounting-header">
    <h2>Journal Entry Workflow</h2>
    <p>Review and approve pending journal entries</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-header">
        <h4>Pending Entries for Approval</h4>
    </div>
    <div class="card-body">
        @if($pendingEntries->isEmpty())
            <p class="text-muted">No pending entries for approval.</p>
        @else
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Entry #</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Creator</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingEntries as $entry)
                    <tr>
                        <td><strong>{{ $entry->entry_number ?? $entry->id }}</strong></td>
                        <td>{{ $entry->entry_date->format('Y-m-d') }}</td>
                        <td>{{ Str::limit($entry->description, 40) }}</td>
                        <td>{{ $entry->creator?->username ?? 'N/A' }}</td>
                        <td>{{ number_format((float) $entry->getTotalDebits(), 2) }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#approveModal{{ $entry->id }}">
                                Review
                            </button>
                        </td>
                    </tr>

                    <!-- Approve Modal -->
                    <div class="modal fade" id="approveModal{{ $entry->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Review Journal Entry {{ $entry->entry_number ?? $entry->id }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('accounting.journal.approve', $entry) }}" method="POST">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <strong>Entry Number:</strong> {{ $entry->entry_number ?? 'N/A' }}<br>
                                                <strong>Date:</strong> {{ $entry->entry_date->format('Y-m-d') }}<br>
                                                <strong>Created By:</strong> {{ $entry->creator?->username ?? 'N/A' }}
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Description:</strong><br>
                                                {{ $entry->description ?? 'No description' }}
                                            </div>
                                        </div>

                                        <h6>Journal Lines</h6>
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Account</th>
                                                    <th>Description</th>
                                                    <th style="text-align: right;">Debit</th>
                                                    <th style="text-align: right;">Credit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($entry->lines as $line)
                                                <tr>
                                                    <td>{{ $line->account_code }} - {{ $line->account->account_name ?? 'N/A' }}</td>
                                                    <td>{{ $line->description ?? '-' }}</td>
                                                    <td style="text-align: right;">{{ number_format((float) $line->debit, 2) }}</td>
                                                    <td style="text-align: right;">{{ number_format((float) $line->credit, 2) }}</td>
                                                </tr>
                                                @endforeach
                                                <tr class="table-light">
                                                    <td colspan="2"><strong>Total</strong></td>
                                                    <td style="text-align: right;"><strong>{{ number_format((float) $entry->getTotalDebits(), 2) }}</strong></td>
                                                    <td style="text-align: right;"><strong>{{ number_format((float) $entry->getTotalCredits(), 2) }}</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <div class="mb-3">
                                            <label for="notes" class="form-label">Approval/Rejection Notes</label>
                                            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Add notes (optional for approval, required for rejection)"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this entry?')">Reject</button>
                                        <button type="submit" name="action" value="approve" class="btn btn-success">Approve & Post</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h4>Recent Activity</h4>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Entry #</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Processed By</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentActivity as $activity)
                <tr>
                    <td>{{ $activity->entry_number ?? $activity->id }}</td>
                    <td>
                        @if($activity->status === 'Posted')
                            <span class="badge bg-success">Posted</span>
                        @elseif($activity->status === 'Reversed')
                            <span class="badge bg-warning">Reversed</span>
                        @elseif($activity->status === 'Pending')
                            <span class="badge bg-info">Pending</span>
                        @else
                            <span class="badge bg-secondary">{{ $activity->status }}</span>
                        @endif
                    </td>
                    <td>{{ $activity->entry_date->format('Y-m-d') }}</td>
                    <td>{{ $activity->approver?->username ?? $activity->creator?->username ?? 'System' }}</td>
                    <td>{{ Str::limit($activity->approval_notes, 30) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
