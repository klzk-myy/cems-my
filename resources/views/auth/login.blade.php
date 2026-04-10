<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CEMS-MY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #1a365d;
            --color-primary-light: #2c5282;
            --color-primary-lighter: #3182ce;
            --color-gold: #D4AF37;
            --color-success: #38a169;
            --color-warning: #dd6b20;
            --color-danger: #e53e3e;
            --color-gray-50: #f7fafc;
            --color-gray-100: #edf2f7;
            --color-gray-200: #e2e8f0;
            --color-gray-300: #cbd5e0;
            --color-gray-400: #a0aec0;
            --color-gray-500: #718096;
            --color-gray-600: #4a5568;
            --color-gray-700: #2d3748;
            --color-gray-800: #1a202c;
            --radius-sm: 4px;
            --radius-md: 6px;
            --radius-lg: 8px;
            --radius-xl: 12px;
            --radius-full: 9999px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --font-heading: 'Merriweather', Georgia, serif;
            --font-body: 'Source Sans 3', -apple-system, sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font-body);
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-container {
            background: white;
            border-radius: var(--radius-xl);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-lg);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo-icon {
            width: 64px;
            height: 64px;
            background: var(--color-gold);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-family: var(--font-heading);
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        .login-logo h1 {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            color: var(--color-primary);
            margin-bottom: 0.25rem;
        }
        .login-logo p {
            color: var(--color-gray-500);
            font-size: 0.875rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--color-gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--color-gray-200);
            border-radius: var(--radius-md);
            font-family: var(--font-body);
            font-size: 0.875rem;
            color: var(--color-gray-800);
            transition: border-color 150ms ease;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--color-primary-lighter);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-family: var(--font-body);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 150ms ease;
        }
        .btn-login:hover {
            background: var(--color-primary-light);
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--color-gray-200);
            font-size: 0.75rem;
            color: var(--color-gray-500);
        }
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border-left: 4px solid var(--color-danger);
        }
        .alert-success {
            background: #c6f6d5;
            color: #276749;
            border-left: 4px solid var(--color-success);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <div class="login-logo-icon">CEMS</div>
            <h1>CEMS-MY</h1>
            <p>Currency Exchange Management System</p>
        </div>

        @if(session('error'))
            <div class="alert alert-error">{{ e(session('error')) }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success">{{ e(session('success')) }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus class="form-input" placeholder="Enter your username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required class="form-input" placeholder="Enter your password">
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="login-footer">
            <p>CEMS-MY v1.0 - Bank Negara Malaysia Compliant MSB Management System</p>
        </div>
    </div>
</body>
</html>
