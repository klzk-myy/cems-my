<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Transaction #{{ $transaction->id }} - CEMS-MY</title>
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
            max-width: 800px;
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
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 0.875rem;
        }
        .btn-primary { background: #3182ce; color: white; }
        .btn-secondary { background: #e2e8f0; color: #4a5568; }
        .btn-danger { background: #e53e3e; color: white; }
        .btn-warning { background: #dd6b20; color: white; }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #fed7d7;
            border-left: 4px solid #e53e3e;
            color: #c53030;
        }
        .warning-header {
            background: #fff5f5;
            border: 2px solid #fc8181;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .warning-header h2 {
            color: #c53030;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .warning-header p {
            color: #742a2a;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #718096; font-weight: 500; }
        .detail-value { color: #2d3748; font-weight: 600; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }
        .form-group .required { color: #e53e3e; }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 0.875rem;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        textarea.form-control { min-height: 120px; resize: vertical; }
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .checkbox-group input[type="checkbox"] {
            margin-top: 0.25rem;
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
        }
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        .consequences {
            background: #fffaf0;
            border-left: 4px solid #dd6b20;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .consequences h3 {
            color: #c05621;
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }
        .consequences ul {
            margin-left: 1.5rem;
            color: #744210;
        }
        .consequences li {
            margin-bottom: 0.5rem;
        }
        .error-text {
            color: #e53e3e;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>CEMS-MY Cancel Transaction</h1>
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
        <!-- Warning Header -->
        <div class="warning-header">
            <h2>⚠️ Cancel Transaction</h2>
            <p>You are about to cancel Transaction #{{ $transaction->id }}</p>
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                <strong>Please correct the following errors:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Transaction Summary -->
        <div class="card">
            <h2>Transaction Summary</h2>
            <div class="detail-row">
                <span class="detail-label">Transaction ID</span>
                <span class="detail-value">#{{ $transaction->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Customer</span>
                <span class="detail-value">
                    {{ str_repeat('*', strlen($transaction->customer->full_name ?? '') - 3) . substr($transaction->customer->full_name ?? 'N/A', -3) }}
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Type</span>
                <span class="detail-value">{{ $transaction->type }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount (Foreign)</span>
                <span class="detail-value">{{ number_format($transaction->amount_foreign, 4) }} {{ $transaction->currency_code }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Rate</span>
                <span class="detail-value">{{ number_format($transaction->rate, 6) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount (MYR)</span>
                <span class="detail-value">RM {{ number_format($transaction->amount_local, 2) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Created</span>
                <span class="detail-value">{{ $transaction->created_at->format('Y-m-d H:i:s') }}</span>
            </div>
        </div>

        <!-- Consequences Warning -->
        <div class="consequences">
            <h3>⚠️ This action will:</h3>
            <ul>
                <li>Create a refund transaction to reverse this transaction</li>
                <li>Reverse the stock position for {{ $transaction->currency_code }}</li>
                <li>Create reversing accounting journal entries</li>
                <li><strong>This action cannot be undone</strong></li>
            </ul>
        </div>

        <!-- Cancellation Form -->
        <div class="card">
            <h2>Cancellation Reason</h2>
            <form action="{{ route('transactions.cancel', $transaction) }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="cancellation_reason">
                        Reason for Cancellation <span class="required">*</span>
                    </label>
                    <textarea
                        name="cancellation_reason"
                        id="cancellation_reason"
                        class="form-control"
                        placeholder="Please provide a detailed reason for cancelling this transaction (minimum 10 characters)..."
                        required
                        minlength="10"
                        maxlength="1000"
                    >{{ old('cancellation_reason') }}</textarea>
                    @error('cancellation_reason')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input
                            type="checkbox"
                            name="confirm_understanding"
                            id="confirm_understanding"
                            value="1"
                            {{ old('confirm_understanding') ? 'checked' : '' }}
                            required
                        >
                        <label for="confirm_understanding">
                            I understand that this action <strong>cannot be undone</strong> and will create a refund transaction, reverse stock movements, and create reversing accounting entries.
                        </label>
                    </div>
                    @error('confirm_understanding')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>

                <div class="actions">
                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-secondary">Back to Transaction</a>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
