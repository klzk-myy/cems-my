<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-header .logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .email-body {
            padding: 30px;
            background-color: #ffffff;
        }
        .email-footer {
            background-color: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 15px 0;
        }
        .btn:hover {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
        }
        .alert-box {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .alert-info {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
        }
        .alert-warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .alert-critical {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
        }
        .detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-label {
            font-weight: 600;
            width: 150px;
            color: #475569;
        }
        .detail-value {
            flex: 1;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-open { background-color: #dbeafe; color: #1e40af; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        .priority-critical { color: #dc2626; font-weight: 600; }
        .priority-high { color: #ea580c; font-weight: 600; }
        .priority-medium { color: #d97706; }
        .priority-low { color: #059669; }
        h2 {
            color: #1e3a8a;
            font-size: 20px;
            margin-top: 0;
        }
        p {
            margin: 0 0 15px 0;
        }
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                width: 100% !important;
            }
            .email-header, .email-body, .email-footer {
                padding: 20px !important;
            }
            .detail-row {
                flex-direction: column;
            }
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td>
                <div class="email-wrapper">
                    <div class="email-header">
                        <div class="logo">{{ config('app.name') }}</div>
                        <h1>@yield('header', 'Notification')</h1>
                    </div>

                    <div class="email-body">
                        @yield('content')
                    </div>

                    <div class="email-footer">
                        <p>This is an automated notification from {{ config('app.name') }}.</p>
                        <p>Bank Negara Malaysia Licensed Money Services Business</p>
                        <p>License: {{ config('app.license_number') }}</p>
                        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
