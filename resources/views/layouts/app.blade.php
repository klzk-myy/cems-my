<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CEMS-MY')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        /* App Layout */
        .app {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background: #1a365d;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .sidebar-header p {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .nav {
            flex: 1;
            padding: 1rem 0;
            display: flex;
            flex-direction: column;
        }

        .nav a {
            color: white;
            text-decoration: none;
            padding: 0.875rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .nav a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #63b3ed;
        }

        .nav a.active {
            background: rgba(255,255,255,0.15);
            border-left-color: #48bb78;
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        .nav-label {
            font-size: 0.875rem;
        }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout-btn {
            color: white;
            text-decoration: none;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 4px;
            transition: background 0.2s;
            cursor: pointer;
            width: 100%;
            background: rgba(255,255,255,0.1);
            border: none;
            font-size: 0.875rem;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Main Content */
        .main {
            flex: 1;
            margin-left: 220px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .content {
            flex: 1;
            padding: 2rem;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }

        /* Common Styles */
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

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }

        tr:hover { background: #f7fafc; }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 0.875rem;
            transition: background 0.2s;
        }

        .btn-primary { background: #3182ce; color: white; }
        .btn-primary:hover { background: #2c5282; }
        .btn-success { background: #38a169; color: white; }
        .btn-success:hover { background: #2f855a; }
        .btn-warning { background: #dd6b20; color: white; }
        .btn-danger { background: #e53e3e; color: white; }

        .footer {
            text-align: center;
            padding: 1.5rem;
            color: #718096;
            font-size: 0.875rem;
            border-top: 1px solid #e2e8f0;
        }

        .alert {
            padding: 0.75rem 1rem;
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

        .alert-info {
            background: #ebf8ff;
            border-left: 4px solid #3182ce;
            color: #2b6cb0;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active { background: #c6f6d5; color: #276749; }
        .status-pending { background: #feebc8; color: #c05621; }
        .status-flagged { background: #fed7d7; color: #c53030; }
        .status-inactive { background: #e2e8f0; color: #718096; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
            }
            .sidebar-header h1,
            .sidebar-header p,
            .nav-label,
            .logout-btn span {
                display: none;
            }
            .main {
                margin-left: 60px;
            }
            .nav a {
                padding: 1rem;
                justify-content: center;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="app">
        <!-- Left Sidebar -->
<aside class="sidebar header">
 <div class="sidebar-header header">
 <h1>CEMS-MY</h1>
 <p>Currency Exchange MSB</p>
 </div>

 <nav class="nav">
                <a href="/" class="{{ request()->is('/') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="/transactions" class="{{ request()->is('transactions*') ? 'active' : '' }}">
                    <span class="nav-icon">💱</span>
                    <span class="nav-label">Transactions</span>
                </a>
                <a href="/customers" class="{{ request()->is('customers*') ? 'active' : '' }}">
                    <span class="nav-icon">👤</span>
                    <span class="nav-label">Customers</span>
                </a>
                <a href="/stock-cash" class="{{ request()->is('stock-cash*') ? 'active' : '' }}">
                    <span class="nav-icon">💰</span>
                    <span class="nav-label">Stock/Cash</span>
                </a>
                <a href="/compliance" class="{{ request()->is('compliance*') ? 'active' : '' }}">
                    <span class="nav-icon">🛡️</span>
                    <span class="nav-label">Compliance</span>
                </a>
                <a href="/accounting" class="{{ request()->is('accounting*') ? 'active' : '' }}">
                    <span class="nav-icon">📚</span>
                    <span class="nav-label">Accounting</span>
                </a>
                <a href="/tasks" class="{{ request()->is('tasks*') ? 'active' : '' }}">
                    <span class="nav-icon">✅</span>
                    <span class="nav-label">Tasks</span>
                </a>
                <a href="/counters" class="{{ request()->is('counters*') ? 'active' : '' }}">
                    <span class="nav-icon">🖥️</span>
                    <span class="nav-label">Counters</span>
                </a>
                <a href="/str" class="{{ request()->is('str*') ? 'active' : '' }}">
                    <span class="nav-icon">📋</span>
                    <span class="nav-label">STR Reports</span>
                </a>
                <a href="/reports" class="{{ request()->is('reports*') ? 'active' : '' }}">
                    <span class="nav-icon">📈</span>
                    <span class="nav-label">Reports</span>
                </a>
                <a href="/audit" class="{{ request()->is('audit*') ? 'active' : '' }}">
                    <span class="nav-icon">🔍</span>
                    <span class="nav-label">Audit</span>
                </a>
                <a href="/users" class="{{ request()->is('users*') ? 'active' : '' }}">
                    <span class="nav-icon">👥</span>
                    <span class="nav-label">Users</span>
                </a>
            </nav>

<div class="sidebar-footer">
 <!-- @csrf -->
 <form id="logout-form" action="/logout" method="POST">
 @csrf
 <button type="submit" class="logout-btn">
 <span class="nav-icon">🚪</span>
 <span>Logout</span>
 </button>
 </form>
 </div>
        </aside>

        <!-- Main Content -->
        <div class="main">
            <div class="content">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif

                @yield('content')
            </div>

            <footer class="footer">
                <p>CEMS-MY v1.0 - Bank Negara Malaysia Compliant MSB Management System</p>
            </footer>
        </div>
    </div>

    @yield('scripts')
</body>
</html>
