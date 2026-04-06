<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Transaction #{{ $transaction->id }} - CEMS-MY</title>
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
        .btn-success { background: #38a169; color: white; }
        .btn-danger { background: #e53e3e; color: white; }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #718096; font-weight: 500; }
        .detail-value { color: #2d3748; font-weight: 600; }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
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
        .confirmation-box {
            background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
            color: white;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .confirmation-box h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .confirmation-box .amount {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 1rem 0;
        }
        .confirmation-box .subtitle {
            opacity: 0.9;
        }
        .confirmation-form {
            background: white;
            border-radius: 8px;
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-family: inherit;
            font-size: 0.875rem;
            resize: vertical;
        }
        .form-group textarea:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .expiry-notice {
            font-size: 0.875rem;
            color: #718096;
            text-align: center;
            margin-top: 1rem;
        }
        .footer {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>CEMS-MY Transaction Confirmation</h1>
        <nav class="nav">
            <a href="/">Dashboard</a>
            <a href="/transactions">Transactions</a>
            <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
        </nav>
        <form id="logout-form" action="/logout" method="POST" style="display: none;">
            @csrf
        </form>
    </header>

    <div class="container">
        <div class="confirmation-box">
            <h3>Large Transaction Confirmation Required</h3>
            <div class="amount">RM {{ number_format($transaction->amount_local, 2) }}</div>
            <div class="subtitle">
                This transaction exceeds RM 50,000 and requires manager confirmation before completion.
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if($confirmation->isExpired())
            <div class="alert alert-error">
                <strong>Confirmation Expired</strong><br>
                This confirmation request has expired. Please request a new confirmation.
            </div>
        @endif

        <!-- Transaction Details -->
        <div class="card">
            <h2>Transaction Details</h2>
            <div class="detail-row">
                <span class="detail-label">Transaction ID</span>
                <span class="detail-value">#{{ $transaction->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Type</span>
                <span class="detail-value">{{ $transaction->type->value }}</span>
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
                <span class="detail-label">CDD Level</span>
                <span class="detail-value">{{ $transaction->cdd_level }}</span>
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
        </div>

        <!-- Confirmation Form -->
        @if(!$confirmation->isExpired() && $confirmation->status === 'pending')
            <div class="confirmation-form">
                <h2 style="text-align: center; margin-bottom: 1.5rem;">Manager Confirmation</h2>

                <form action="/transactions/{{ $transaction->id }}/confirm" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="notes">Confirmation Notes (Optional)</label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="3"
                            placeholder="Add any notes regarding this confirmation..."
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <span style="color: #e53e3e; font-size: 0.875rem;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="confirmation_action" value="confirm" class="btn btn-success" onclick="return confirm('Are you sure you want to CONFIRM this transaction?');">
                            Confirm Transaction
                        </button>
                        <button type="submit" name="confirmation_action" value="reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to REJECT this transaction?');">
                            Reject Transaction
                        </button>
                    </div>
                </form>

                @if($confirmation->expires_at)
                    <p class="expiry-notice">
                        This confirmation request expires at {{ $confirmation->expires_at->format('H:i:s') }}
                    </p>
                @endif
            </div>
        @elseif($confirmation->status === 'confirmed')
            <div class="alert alert-warning">
                <strong>Already Confirmed</strong><br>
                This transaction has already been confirmed.
            </div>
        @elseif($confirmation->status === 'rejected')
            <div class="alert alert-error">
                <strong>Rejected</strong><br>
                This transaction was rejected during confirmation.
            </div>
        @endif

        <div style="text-align: center; margin-top: 1rem;">
            <a href="/transactions/{{ $transaction->id }}" class="btn btn-primary">View Transaction Details</a>
        </div>
    </div>

    <footer class="footer">
        <p>CEMS-MY v1.0 - Bank Negara Malaysia Compliant MSB Management System</p>
    </footer>
</body>
</html>
