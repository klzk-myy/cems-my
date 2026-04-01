@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Journal Entries</h4>
                    <a href="{{ route('accounting.journal.create') }}" class="btn btn-primary">Create Entry</a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th>Debits</th>
                                <th>Credits</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                            <tr>
                                <td>{{ $entry->id }}</td>
                                <td>{{ $entry->entry_date }}</td>
                                <td>{{ $entry->reference_type }} {{ $entry->reference_id }}</td>
                                <td>{{ Str::limit($entry->description, 50) }}</td>
                                <td>{{ number_format($entry->getTotalDebits(), 2) }}</td>
                                <td>{{ number_format($entry->getTotalCredits(), 2) }}</td>
                                <td>
                                    @if($entry->isPosted())
                                        <span class="badge bg-success">Posted</span>
                                    @elseif($entry->isReversed())
                                        <span class="badge bg-warning">Reversed</span>
                                    @else
                                        <span class="badge bg-secondary">Draft</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('accounting.journal.show', $entry) }}" class="btn btn-sm btn-info">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    {{ $entries->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
