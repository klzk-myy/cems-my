<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction #{{ $transaction->id }} - CEMS-MY</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .header {
            background: #1a365d;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 1.5rem; }
        .nav { display: flex; gap: 1rem; }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .nav a:hover { background: rgba(255,255,255,0.1); }
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .card h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #1a365d;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 0.875rem;
        }
.btn-primary { background: #3182ce; color: white; }
    .btn-success { background: #38a169; color: white; }
    .btn-secondary { background: #e2e8f0; color: #4a5568; }
    .btn-danger { background: #e53e3e; color: white; }
    .btn-warning { background: #dd6b20; color: white; }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #718096;
            font-weight: 500;
        }
        .detail-value {
            color: #2d3748;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status-completed { background: #c6f6d5; color: #276749; }
        .status-pending { background: #feebc8; color: #c05621; }
        .status-onhold { background: #fed7d7; color: #c53030; }
        .type-buy { color: #38a169; font-weight: 700; }
        .type-sell { color: #e53e3e; font-weight: 700; }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #c6f6d5;
            border-left: 4px solid #38a169;
            color: #276749;
        }
        .alert-warning {
            background: #fffaf0;
            border-left: 4px solid #dd6b20;
            color: #c05621;
        }
        .alert-error {
            background: #fed7d7;
            border-left: 4px solid #e53e3e;
            color: #c53030;
        }
        .receipt-box {
            background: #f7fafc;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
        }
        .receipt-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a365d;
            margin-bottom: 0.5rem;
        }
        .receipt-id {
            font-size: 1.25rem;
            color: #4a5568;
            margin-bottom: 1rem;
        }
        .amount-display {
            font-size: 2rem;
            font-weight: 700;
            color: #1a365d;
            margin: 1rem 0;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .footer {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }
        .compliance-box {
            background: #fffaf0;
            border: 2px solid #dd6b20;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .compliance-box h4 {
            color: #c05621;
            margin-bottom: 0.5rem;
        }
        .journal-entry {
            background: #f7fafc;
            border-left: 4px solid #3182ce;
            padding: 1rem;
            margin-bottom: 0.5rem;
            font-family: monospace;
        }
        .journal-dr { color: #38a169; }
        .journal-cr { color: #e53e3e; }
    </style>
</head>
<body>
    <header class="header">
        <h1>CEMS-MY Transaction Details</h1>
        <nav class="nav">
            <a href="/">Dashboard</a>
            <a href="/transactions">Transactions</a>
            <a href="/stock-cash">Stock/Cash</a>
            <a href="/compliance">Compliance</a>
            <a href="/accounting">Accounting</a>
            <a href="/reports">Reports</a>
            <a href="/users">Users</a>
            <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
        </nav>
        <form id="logout-form" action="/logout" method="POST" style="display: none;">
            @csrf
        </form>
    </header>

    <div class="container">
        <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="color: #2d3748; margin-bottom: 0.5rem;">Transaction #{{ $transaction->id }}</h2>
                <p style="color: #718096;">Created {{ $transaction->created_at->diffForHumans() }} by {{ $transaction->user->username ?? 'Unknown' }}</p>
            </div>
            <div>
                <a href="/transactions" class="btn btn-secondary">Back to List</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if($transaction->status === 'Pending')
            <div class="compliance-box">
                <h4>⏳ Pending Approval</h4>
                <p>This transaction requires manager approval (≥ RM 50,000).</p>
                @if(auth()->user()->isManager())
                    <form action="/transactions/{{ $transaction->id }}/approve" method="POST" style="margin-top: 1rem;">
                        @csrf
                        <button type="submit" class="btn btn-success">Approve Transaction</button>
                    </form>
                @endif
            </div>
        @endif

        @if($transaction->status === 'OnHold')
            <div class="alert alert-error">
                <strong>Transaction On Hold:</strong> {{ $transaction->hold_reason }}
            </div>
        @endif

        <!-- Receipt -->
        <div class="card">
            <div class="receipt-box">
                <div class="receipt-title">CEMS-MY</div>
                <div class="receipt-id">Transaction Receipt</div>
                <div class="amount-display">
                    @if($transaction->type === 'Buy')
                        <span style="color: #38a169;">+</span>
                    @else
                        <span style="color: #e53e3e;">-</span>
                    @endif
                    {{ number_format($transaction->amount_foreign, 4) }} {{ $transaction->currency_code }}
                </div>
                <div style="color: #718096;">
                    @ {{ number_format($transaction->rate, 6) }} MYR/{{ $transaction->currency_code }}
                </div>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 2px dashed #cbd5e0;">
                    <div style="font-size: 1.5rem; font-weight: 700;">
                        RM {{ number_format($transaction->amount_local, 2) }}
                    </div>
                    <div style="color: #718096;">Total Amount</div>
                </div>
                <div style="margin-top: 1rem;">
                    @php
                        $statusClass = match($transaction->status) {
                            'Completed' => 'status-completed',
                            'Pending' => 'status-pending',
                            'OnHold' => 'status-onhold',
                            default => 'status-pending'
                        };
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ $transaction->status }}</span>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <!-- Transaction Details -->
            <div class="card">
                <h2>Transaction Details</h2>
                <div class="detail-row">
                    <span class="detail-label">Transaction ID</span>
                    <span class="detail-value">#{{ $transaction->id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type</span>
                    <span class="detail-value type-{{ strtolower($transaction->type->value) }}">{{ $transaction->type->value }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Currency</span>
                    <span class="detail-value">{{ $transaction->currency_code }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Foreign Amount</span>
                    <span class="detail-value">{{ number_format($transaction->amount_foreign, 4) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Exchange Rate</span>
                    <span class="detail-value">{{ number_format($transaction->rate, 6) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Local Amount (MYR)</span>
                    <span class="detail-value">RM {{ number_format($transaction->amount_local, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <span class="status-badge {{ $statusClass }}">{{ $transaction->status }}</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">CDD Level</span>
                    <span class="detail-value">{{ $transaction->cdd_level }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created At</span>
                    <span class="detail-value">{{ $transaction->created_at->format('Y-m-d H:i:s') }}</span>
                </div>
            </div>

            <!-- Customer Details -->
            <div class="card">
                <h2>Customer Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Name</span>
                    <span class="detail-value">{{ $transaction->customer->full_name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">ID Type</span>
                    <span class="detail-value">{{ $transaction->customer->id_type ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">ID Number</span>
                    <span class="detail-value">{{ $transaction->customer->id_number ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Risk Rating</span>
                    <span class="detail-value">{{ $transaction->customer->risk_rating ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Purpose</span>
                    <span class="detail-value">{{ $transaction->purpose }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Source of Funds</span>
                    <span class="detail-value">{{ $transaction->source_of_funds }}</span>
                </div>
            </div>
        </div>

        <!-- Processing Details -->
        <div class="card">
            <h2>Processing Details</h2>
            <div class="detail-row">
                <span class="detail-label">Processed By</span>
                <span class="detail-value">{{ $transaction->user->username ?? 'N/A' }}</span>
            </div>
            @if($transaction->approved_by)
                <div class="detail-row">
                    <span class="detail-label">Approved By</span>
                    <span class="detail-value">{{ $transaction->approver->username ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Approved At</span>
                    <span class="detail-value">{{ $transaction->approved_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</span>
                </div>
            @endif
            @if($transaction->hold_reason)
                <div class="detail-row">
                    <span class="detail-label">Hold Reason</span>
                    <span class="detail-value" style="color: #e53e3e;">{{ $transaction->hold_reason }}</span>
                </div>
            @endif
        </div>

        <!-- Compliance Flags -->
        @if($transaction->flags && $transaction->flags->count() > 0)
            <div class="card">
                <h2>Compliance Flags</h2>
                @foreach($transaction->flags as $flag)
                    <div class="alert alert-warning" style="margin-bottom: 0.5rem;">
                        <strong>{{ $flag->flag_type }}:</strong> {{ $flag->flag_reason }}
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Journal Entries -->
        @if($transaction->status === 'Completed')
            <div class="card">
                <h2>Accounting Journal Entries</h2>
                @if($transaction->type === 'Buy')
                    <div class="journal-entry">
                        <div class="journal-dr">Dr Foreign Currency Inventory (1100)</div>
                        <div style="margin-left: 2rem;">RM {{ number_format($transaction->amount_local, 2) }}</div>
                    </div>
                    <div class="journal-entry">
                        <div class="journal-cr">Cr Cash - MYR (1000)</div>
                        <div style="margin-left: 2rem;">RM {{ number_format($transaction->amount_local, 2) }}</div>
                    </div>
                    <div style="margin-top: 1rem; color: #718096; font-size: 0.875rem;">
                        <strong>Narration:</strong> Buy {{ number_format($transaction->amount_foreign, 4) }} {{ $transaction->currency_code }} @ {{ number_format($transaction->rate, 6) }}
                    </div>
                @else
                    <div class="journal-entry">
                        <div class="journal-dr">Dr Cash - MYR (1000)</div>
                        <div style="margin-left: 2rem;">RM {{ number_format($transaction->amount_local, 2) }}</div>
                    </div>
                    <div class="journal-entry">
                        <div class="journal-cr">Cr Foreign Currency Inventory (1100)</div>
                        <div style="margin-left: 2rem;">RM {{ number_format($transaction->amount_local, 2) }}</div>
                    </div>
                    @php
                        $position = \App\Models\CurrencyPosition::where('currency_code', $transaction->currency_code)->first();
                        $avgCost = $position ? $position->avg_cost_rate : $transaction->rate;
                        $costBasis = $transaction->amount_foreign * $avgCost;
                        $revenue = $transaction->amount_local - $costBasis;
                    @endphp
                    @if($revenue >= 0)
                        <div class="journal-entry">
                            <div class="journal-cr">Cr Revenue - Forex (4000)</div>
                            <div style="margin-left: 2rem;">RM {{ number_format($revenue, 2) }}</div>
                        </div>
                    @else
                        <div class="journal-entry">
                            <div class="journal-dr">Dr Expense - Forex Loss (5000)</div>
                            <div style="margin-left: 2rem;">RM {{ number_format(abs($revenue), 2) }}</div>
                        </div>
                    @endif
                    <div style="margin-top: 1rem; color: #718096; font-size: 0.875rem;">
                        <strong>Narration:</strong> Sell {{ number_format($transaction->amount_foreign, 4) }} {{ $transaction->currency_code }} @ {{ number_format($transaction->rate, 6) }}
                    </div>
                @endif
            </div>
        @endif

        <!-- Actions -->
        <div class="card" style="text-align: center;">
            <a href="/transactions" class="btn btn-secondary">Back to Transactions</a>
            <a href="/transactions/create" class="btn btn-success">New Transaction</a>
            @if($transaction->status === 'Completed')
            <a href="/transactions/{{ $transaction->id }}/receipt" class="btn btn-primary" target="_blank">
                Print Receipt
            </a>
            @endif
@if($transaction->status === 'Pending' && auth()->user()->isManager())
  <form action="/transactions/{{ $transaction->id }}/approve" method="POST" style="display: inline;">
  @csrf
  <button type="submit" class="btn btn-success">Approve</button>
  </form>
  @endif
  @if($transaction->isRefundable() && (auth()->user()->isManager() || auth()->user()->isAdmin() || auth()->user()->id === $transaction->user_id))
  <a href="{{ route('transactions.cancel.show', $transaction) }}" class="btn btn-danger">
  Cancel Transaction
  </a>
  @endif
 </div>
    </div>

    <footer class="footer">
        <p>CEMS-MY v1.0 - Bank Negara Malaysia Compliant MSB Management System</p>
    </footer>
</body>
</html>
