@extends('layouts.app')

@section('title', 'Customer Management - CEMS-MY')

@section('styles')
<style>
    .customers-header {
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .customers-header h2 {
        color: #2d3748;
        margin-bottom: 0.25rem;
    }
    .customers-header p {
        color: #718096;
        font-size: 0.875rem;
    }

    .filters {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .filter-group label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #4a5568;
        text-transform: uppercase;
    }
    .filter-group input,
    .filter-group select {
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    .filter-actions {
        display: flex;
        gap: 0.5rem;
        align-items: flex-end;
    }

    .risk-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .risk-low { background: #c6f6d5; color: #276749; }
    .risk-medium { background: #feebc8; color: #c05621; }
    .risk-high { background: #fed7d7; color: #c53030; }

    .pep-badge {
        display: inline-block;
        padding: 0.125rem 0.5rem;
        border-radius: 4px;
        font-size: 0.625rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .pep-yes { background: #fed7d7; color: #c53030; }
    .pep-no { background: #e2e8f0; color: #718096; }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-active { background: #c6f6d5; color: #276749; }
    .status-inactive { background: #e2e8f0; color: #718096; }

    .btn-sm { padding: 0.375rem 0.75rem; font-size: 0.75rem; }
    .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .pagination a, .pagination span {
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
    }
    .pagination a { background: #e2e8f0; color: #4a5568; }
    .pagination span { background: #3182ce; color: white; }

    .table-responsive {
        overflow-x: auto;
    }

    .results-info {
        font-size: 0.875rem;
        color: #718096;
        margin-bottom: 1rem;
    }
</style>
@endsection

@section('content')
<div class="customers-header">
    <div>
        <h2>Customer Management</h2>
        <p>Manage customer profiles, KYC documents, and risk assessments</p>
    </div>
    <a href="/customers/create" class="btn btn-success">+ Add New Customer</a>
</div>

<!-- Filters -->
<div class="filters">
    <form method="GET" action="{{ route('customers.index') }}">
        <div class="filters-grid">
            <div class="filter-group">
                <label for="search">Search by Name</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Customer name...">
            </div>
            <div class="filter-group">
                <label for="risk_rating">Risk Rating</label>
                <select id="risk_rating" name="risk_rating">
                    <option value="">All Ratings</option>
                    @foreach($riskRatings as $rating)
                        <option value="{{ $rating }}" {{ request('risk_rating') == $rating ? 'selected' : '' }}>
                            {{ $rating }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="nationality">Nationality</label>
                <select id="nationality" name="nationality">
                    <option value="">All</option>
                    @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" {{ request('nationality') == $nat ? 'selected' : '' }}>
                            {{ $nat }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active">
                    <option value="">All</option>
                    <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="pep_status">PEP Status</label>
                <select id="pep_status" name="pep_status">
                    <option value="">All</option>
                    <option value="1" {{ request('pep_status') == '1' ? 'selected' : '' }}>PEP</option>
                    <option value="0" {{ request('pep_status') == '0' ? 'selected' : '' }}>Non-PEP</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('customers.index') }}" class="btn btn-sm" style="background: #e2e8f0; color: #4a5568;">Clear</a>
            </div>
        </div>
    </form>
</div>

<!-- Customer List -->
<div class="card">
    <div class="results-info">
        Showing {{ $customers->count() }} of {{ $customers->total() }} customers
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>ID Type</th>
                    <th>Nationality</th>
                    <th>Risk Rating</th>
                    <th>PEP</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td>{{ $customer->id }}</td>
                    <td>
                        <strong>{{ $customer->full_name }}</strong>
                        @if($customer->risk_rating === 'High')
                            <span class="risk-badge risk-high" style="margin-left: 0.5rem;">High Risk</span>
                        @endif
                    </td>
                    <td>{{ $customer->id_type }}</td>
                    <td>{{ $customer->nationality }}</td>
                    <td>
                        <span class="risk-badge risk-{{ strtolower($customer->risk_rating) }}">
                            {{ $customer->risk_rating }}
                        </span>
                    </td>
                    <td>
                        @if($customer->pep_status)
                            <span class="pep-badge pep-yes">PEP</span>
                        @else
                            <span class="pep-badge pep-no">No</span>
                        @endif
                    </td>
                    <td>
                        @if($customer->is_active ?? true)
                            <span class="status-badge status-active">Active</span>
                        @else
                            <span class="status-badge status-inactive">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $customer->created_at->format('Y-m-d') }}</td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-primary btn-sm">View</a>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm" style="background: #ed8936; color: white;">Edit</a>
                            <a href="{{ route('customers.kyc', $customer) }}" class="btn btn-sm" style="background: #38a169; color: white;">KYC</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem; color: #718096;">
                        No customers found. @if(request()->anyFilled(['search', 'risk_rating', 'nationality', 'is_active', 'pep_status'])) Try adjusting your filters. @else <a href="{{ route('customers.create') }}">Add your first customer</a>. @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $customers->links() }}
    </div>
</div>

<!-- Quick Stats -->
<div class="grid">
    <div class="card">
        <h2>Customer Summary</h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; text-align: center;">
            <div>
                <div style="font-size: 2rem; font-weight: 700; color: #3182ce;">{{ $customers->total() }}</div>
                <div style="color: #718096; font-size: 0.875rem;">Total Customers</div>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: 700; color: #38a169;">{{ $customers->where('risk_rating', 'Low')->count() + $customers->where('risk_rating', 'Medium')->count() }}</div>
                <div style="color: #718096; font-size: 0.875rem;">Low/Medium Risk</div>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: 700; color: #e53e3e;">{{ $customers->where('risk_rating', 'High')->count() }}</div>
                <div style="color: #718096; font-size: 0.875rem;">High Risk</div>
            </div>
        </div>
    </div>
</div>
@endsection
