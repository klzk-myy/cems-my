<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Transaction History Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #007bff;
            font-size: 18px;
        }
        .header-meta {
            margin-top: 10px;
            font-size: 10px;
            color: #666;
        }
        .customer-info {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .customer-info h2 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        .customer-info table {
            width: 100%;
        }
        .customer-info td {
            padding: 3px 10px 3px 0;
            vertical-align: top;
        }
        .customer-info .label {
            font-weight: bold;
            color: #666;
        }
        .filters {
            background-color: #e9ecef;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 3px;
            font-size: 9px;
        }
        .filters strong {
            color: #495057;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th {
            background-color: #343a40;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        table.data-table td {
            padding: 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 9px;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-buy {
            background-color: #28a745;
            color: white;
        }
        .badge-sell {
            background-color: #17a2b8;
            color: white;
        }
        .badge-completed {
            background-color: #28a745;
            color: white;
        }
        .badge-pending {
            background-color: #ffc107;
            color: black;
        }
        .badge-hold {
            background-color: #6c757d;
            color: white;
        }
        .summary {
            margin-top: 30px;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        .summary h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #333;
        }
        .summary-table {
            width: 50%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        .summary-table .total {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 8px;
            color: #666;
            text-align: center;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Customer Transaction History Report</h1>
        <div class="header-meta">
            <strong>Generated:</strong> {{ $generatedAt->format('Y-m-d H:i:s') }}<br>
            <strong>Report ID:</strong> CH-{{ $customer->id }}-{{ $generatedAt->format('YmdHis') }}
        </div>
    </div>

    <div class="customer-info">
        <h2>Customer Information</h2>
        <table>
            <tr>
                <td><span class="label">Customer Name:</span></td>
                <td>{{ $customer->full_name }}</td>
                <td><span class="label">Customer ID:</span></td>
                <td>{{ $customer->id }}</td>
            </tr>
            <tr>
                <td><span class="label">ID Type:</span></td>
                <td>{{ $customer->id_type }}</td>
                <td><span class="label">Phone:</span></td>
                <td>{{ $customer->phone }}</td>
            </tr>
            <tr>
                <td><span class="label">Risk Rating:</span></td>
                <td>{{ $customer->risk_rating }}</td>
                <td><span class="label">CDD Level:</span></td>
                <td>{{ $customer->cdd_level }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($filters['date_from']) || !empty($filters['date_to']))
    <div class="filters">
        <strong>Applied Filters:</strong>
        @if(!empty($filters['date_from']))
            Date From: {{ $filters['date_from'] }}
        @endif
        @if(!empty($filters['date_to']))
            | Date To: {{ $filters['date_to'] }}
        @endif
    </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Currency</th>
                <th class="text-right">Foreign Amount</th>
                <th class="text-right">MYR Amount</th>
                <th class="text-right">Rate</th>
                <th>Status</th>
                <th>Processed By</th>
                <th>Purpose</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
            <tr>
                <td>{{ $row['Transaction ID'] }}</td>
                <td>{{ $row['Date'] }}</td>
                <td>
                    <span class="badge badge-{{ strtolower($row['Type']) }}">
                        {{ $row['Type'] }}
                    </span>
                </td>
                <td>{{ $row['Currency'] }}</td>
                <td class="text-right">{{ number_format($row['Foreign Amount'], 2) }}</td>
                <td class="text-right">{{ number_format($row['MYR Amount'], 2) }}</td>
                <td class="text-right">{{ number_format($row['Rate'], 6) }}</td>
                <td>
                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $row['Status'])) }}">
                        {{ $row['Status'] }}
                    </span>
                </td>
                <td>{{ $row['Processed By'] }}</td>
                <td>{{ $row['Purpose'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center">No transactions found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if(count($data) > 0)
    <div class="summary">
        <h3>Summary</h3>
        <table class="summary-table">
            <tr>
                <td>Total Transactions:</td>
                <td class="text-right">{{ count($data) }}</td>
            </tr>
            @php
                $buyTransactions = collect($data)->where('Type', 'Buy');
                $sellTransactions = collect($data)->where('Type', 'Sell');
                $totalBuyAmount = $buyTransactions->sum('MYR Amount');
                $totalSellAmount = $sellTransactions->sum('MYR Amount');
            @endphp
            <tr>
                <td>Buy Transactions:</td>
                <td class="text-right">{{ $buyTransactions->count() }}</td>
            </tr>
            <tr>
                <td>Sell Transactions:</td>
                <td class="text-right">{{ $sellTransactions->count() }}</td>
            </tr>
            <tr class="total">
                <td>Total Buy (MYR):</td>
                <td class="text-right">{{ number_format($totalBuyAmount, 2) }}</td>
            </tr>
            <tr class="total">
                <td>Total Sell (MYR):</td>
                <td class="text-right">{{ number_format($totalSellAmount, 2) }}</td>
            </tr>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This report was generated by CEMS-MY (Currency Exchange Management System)</p>
        <p>Document is confidential and for authorized use only. Page 1 of 1</p>
    </div>
</body>
</html>
