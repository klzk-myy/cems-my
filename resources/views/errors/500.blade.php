<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error | CEMS-MY</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            max-width: 500px;
            padding: 2rem;
        }
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #e53e3e;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a365d;
            margin-bottom: 0.75rem;
        }
        .error-message {
            font-size: 1rem;
            color: #718096;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3182ce;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #2c5282;
        }
        .footer {
            margin-top: 3rem;
            color: #a0aec0;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <div class="error-code">500</div>
        <h1 class="error-title">Server Error</h1>
        <p class="error-message">
            Something went wrong on our end.<br>
            Our team has been notified and is working to fix the issue.
        </p>
        <a href="/" class="btn">Back to Dashboard</a>
        <div class="footer">
            <p>CEMS-MY v1.0 - Bank Negara Malaysia Compliant MSB Management System</p>
        </div>
    </div>
</body>
</html>