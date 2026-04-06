<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Report - CEMS-MY</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 10px;
            color: #333;
            background: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1a365d;
        }
        .header h1 {
            font-size: 18px;
            color: #1a365d;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 11px;
            color: #718096;
        }
        .meta {
            margin-bottom: 15px;
            font-size: 10px;
            color: #4a5568;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        .severity-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .severity-info { background: #bee3f8; color: #2c5282; }
        .severity-warning { background: #fefcbf; color: #744210; }
        .severity-error { background: #fed7d7; color: #c53030; }
        .severity-critical { background: #fc8181; color: #742a2a; }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #718096;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CEMS-MY Audit Log Report</h1>
        <p>Currency Exchange Management System - Malaysia</p>
    </div>

    <div class="meta">
        <strong>Period:</strong> {{ $dateFrom }} to {{ $dateTo }} |
        <strong>Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th style="width: 15%;">Timestamp</th>
                <th style="width: 12%;">User</th>
                <th style="width: 15%;">Action</th>
                <th style="width: 18%;">Entity</th>
                <th style="width: 10%;">Severity</th>
                <th style="width: 10%;">IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>{{ $log->id }}</td>
                <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                <td>{{ $log->user->username ?? 'System' }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->entity_type ? $log->entity_type . ' #' . $log->entity_id : 'N/A' }}</td>
                <td>
                    <span class="severity-badge severity-{{ strtolower($log->severity ?? 'info') }}">
                        {{ $log->severity ?? 'INFO' }}
                    </span>
                </td>
                <td>{{ $log->ip_address }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #718096;">
                    No audit log entries found for the specified period.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>CEMS-MY v1.0 - Bank Negara Malaysia Compliant MSB Management System</p>
        <p>Confidential - For Internal Use Only</p>
    </div>
</body>
</html>
