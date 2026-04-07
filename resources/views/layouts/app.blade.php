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
            width: 240px;
            background: linear-gradient(180deg, #1a365d 0%, #2d4a7c 100%);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
        }

        .sidebar-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
        }

        .sidebar-header h1 {
            font-size: 1.125rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .sidebar-header p {
            font-size: 0.7rem;
            opacity: 0.6;
            margin-top: 0.2rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Navigation */
        .nav {
            flex: 1;
            padding: 0.75rem 0;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.4);
            padding: 0.5rem 1.5rem;
            margin-bottom: 0.25rem;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 0.625rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
            font-size: 0.85rem;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: rgba(255,255,255,0.3);
        }

        .nav-link.active {
            background: rgba(255,255,255,0.12);
            color: white;
            border-left-color: #48bb78;
        }

        /* Dropdown Submenu */
        .nav-submenu {
            display: none;
            flex-direction: column;
            background: rgba(0,0,0,0.15);
            margin: 0.25rem 0 0.25rem 1rem;
            border-radius: 6px;
            overflow: hidden;
        }

        .nav-group.open > .nav-submenu,
        .nav-group:hover > .nav-submenu {
            display: flex;
        }

        .nav-submenu .nav-link {
            padding: 0.5rem 1rem 0.5rem 2rem;
            font-size: 0.8rem;
            border-left: none;
        }

        .nav-submenu .nav-link:hover {
            background: rgba(255,255,255,0.1);
            border-left: none;
        }

        .nav-submenu .nav-link.active {
            background: rgba(72, 187, 120, 0.2);
            border-left: none;
        }

        /* Icons */
        .nav-icon {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nav-icon svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .nav-arrow {
            margin-left: auto;
            font-size: 0.6rem;
            opacity: 0.5;
            transition: transform 0.2s;
        }

        .nav-group.open > .nav-link .nav-arrow {
            transform: rotate(180deg);
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
        }

        .logout-btn {
            color: white;
            text-decoration: none;
            padding: 0.625rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 6px;
            transition: background 0.15s;
            cursor: pointer;
            width: 100%;
            background: rgba(255,255,255,0.1);
            border: none;
            font-size: 0.85rem;
            font-family: inherit;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Main Content */
        .main {
            flex: 1;
            margin-left: 240px;
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
        .btn-warning:hover { background: #c05621; }
        .btn-danger { background: #e53e3e; color: white; }
        .btn-danger:hover { background: #c53030; }

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
        @media (max-width: 1024px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header h1,
            .sidebar-header p,
            .nav-section-label,
            .nav-link span:not(.nav-icon),
            .nav-arrow,
            .logout-btn span {
                display: none;
            }
            .nav-link {
                justify-content: center;
                padding: 0.75rem;
            }
            .nav-submenu {
                display: none !important;
            }
            .main {
                margin-left: 70px;
            }
            .sidebar-footer {
                padding: 1rem;
            }
            .logout-btn {
                justify-content: center;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="app">
        <!-- Left Sidebar - Organized by Function -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>CEMS-MY</h1>
                <p>Currency Exchange MSB</p>
            </div>

            <nav class="nav">
                {{-- ============================================================
                    OPERATIONS - Daily operational tasks
                ============================================================ --}}
                <div class="nav-section">
                    <div class="nav-section-label">Operations</div>

                    <div class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                            </span>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->is('transactions*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><path d="M8 7h12M8 12h12M8 17h12M4 7h.01M4 12h.01M4 17h.01"/></svg>
                            </span>
                            <span>Transactions</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="{{ route('customers.index') }}" class="nav-link {{ request()->is('customers*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </span>
                            <span>Customers</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="{{ route('counters.index') }}" class="nav-link {{ request()->is('counters*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h.01M6 12h.01M6 16h.01M10 8h8M10 12h8M10 16h8"/></svg>
                            </span>
                            <span>Counters</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="{{ route('stock-cash.index') }}" class="nav-link {{ request()->is('stock-cash*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </span>
                            <span>Stock & Cash</span>
                        </a>
                    </div>
                </div>

                {{-- ============================================================
                    COMPLIANCE & AML - BNM regulatory compliance
                ============================================================ --}}
                <div class="nav-section">
                    <div class="nav-section-label">Compliance & AML</div>

                    <div class="nav-group">
                        <a href="{{ route('compliance') }}" class="nav-link {{ request()->is('compliance') && !request()->is('compliance/flagged*') && !request()->is('compliance/edd*') && !request()->is('compliance/rules*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </span>
                            <span>Compliance</span>
                            <span class="nav-arrow">▼</span>
                        </a>
                        <div class="nav-submenu">
                            <a href="{{ route('compliance.flagged') }}" class="nav-link {{ request()->is('compliance/flagged*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/></svg>
                                </span>
                                <span>Flagged Transactions</span>
                            </a>
                            <a href="{{ route('compliance.edd.index') }}" class="nav-link {{ request()->is('compliance/edd*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                </span>
                                <span>EDD Records</span>
                            </a>
                            <a href="{{ route('compliance.rules.index') }}" class="nav-link {{ request()->is('compliance/rules*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                </span>
                                <span>AML Rules</span>
                            </a>
                        </div>
                    </div>

                    <div class="nav-item">
                        <a href="{{ route('str.index') }}" class="nav-link {{ request()->is('str*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            </span>
                            <span>STR Reports</span>
                        </a>
                    </div>
                </div>

                {{-- ============================================================
                    ACCOUNTING & FINANCE - Double-entry accounting
                ============================================================ --}}
                <div class="nav-section">
                    <div class="nav-section-label">Accounting</div>

                    <div class="nav-group">
                        <a href="{{ route('accounting.index') }}" class="nav-link {{ request()->is('accounting') && !request()->is('accounting/journal*') && !request()->is('accounting/ledger*') && !request()->is('accounting/trial-balance*') && !request()->is('accounting/profit-loss*') && !request()->is('accounting/balance-sheet*') && !request()->is('accounting/cash-flow*') && !request()->is('accounting/ratios*') && !request()->is('accounting/periods*') && !request()->is('accounting/fiscal-years*') && !request()->is('accounting/revaluation*') && !request()->is('accounting/reconciliation*') && !request()->is('accounting/budget*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>
                            </span>
                            <span>Accounting</span>
                            <span class="nav-arrow">▼</span>
                        </a>
                        <div class="nav-submenu">
                            <a href="{{ route('accounting.journal') }}" class="nav-link {{ request()->is('accounting/journal') && !request()->is('accounting/journal/create*') && !request()->is('accounting/journal/workflow*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </span>
                                <span>Journal Entries</span>
                            </a>
                            <a href="{{ route('accounting.journal.create') }}" class="nav-link {{ request()->is('accounting/journal/create*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                </span>
                                <span>New Entry</span>
                            </a>
                            <a href="{{ route('accounting.journal.workflow') }}" class="nav-link {{ request()->is('accounting/journal/workflow*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                </span>
                                <span>Workflow</span>
                            </a>
                            <a href="{{ route('accounting.ledger') }}" class="nav-link {{ request()->is('accounting/ledger*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                                </span>
                                <span>Ledger</span>
                            </a>
                            <a href="{{ route('accounting.trial-balance') }}" class="nav-link {{ request()->is('accounting/trial-balance*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><path d="M12 3v18M3 9h18M3 15h18"/></svg>
                                </span>
                                <span>Trial Balance</span>
                            </a>
                            <a href="{{ route('accounting.profit-loss') }}" class="nav-link {{ request()->is('accounting/profit-loss*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                                </span>
                                <span>Profit & Loss</span>
                            </a>
                            <a href="{{ route('accounting.balance-sheet') }}" class="nav-link {{ request()->is('accounting/balance-sheet*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                </span>
                                <span>Balance Sheet</span>
                            </a>
                            <a href="{{ route('accounting.cash-flow') }}" class="nav-link {{ request()->is('accounting/cash-flow*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </span>
                                <span>Cash Flow</span>
                            </a>
                            <a href="{{ route('accounting.ratios') }}" class="nav-link {{ request()->is('accounting/ratios*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                </span>
                                <span>Financial Ratios</span>
                            </a>
                            <a href="{{ route('accounting.periods') }}" class="nav-link {{ request()->is('accounting/periods*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </span>
                                <span>Periods</span>
                            </a>
                            <a href="{{ route('accounting.fiscal-years') }}" class="nav-link {{ request()->is('accounting/fiscal-years*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="14" x2="16" y2="14"/></svg>
                                </span>
                                <span>Fiscal Years</span>
                            </a>
                            <a href="{{ route('accounting.revaluation') }}" class="nav-link {{ request()->is('accounting/revaluation*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                </span>
                                <span>Revaluation</span>
                            </a>
                            <a href="{{ route('accounting.reconciliation') }}" class="nav-link {{ request()->is('accounting/reconciliation*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                </span>
                                <span>Reconciliation</span>
                            </a>
                            <a href="{{ route('accounting.budget') }}" class="nav-link {{ request()->is('accounting/budget*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
                                </span>
                                <span>Budget</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ============================================================
                    REPORTS - BNM compliance reporting
                ============================================================ --}}
                <div class="nav-section">
                    <div class="nav-section-label">Reports</div>

                    <div class="nav-group">
                        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->is('reports') && !request()->is('reports/lctr*') && !request()->is('reports/lmca*') && !request()->is('reports/quarterly-lvr*') && !request()->is('reports/position-limit*') && !request()->is('reports/msb2*') && !request()->is('reports/history*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            </span>
                            <span>Reports</span>
                            <span class="nav-arrow">▼</span>
                        </a>
                        <div class="nav-submenu">
                            <a href="{{ route('reports.msb2') }}" class="nav-link {{ request()->is('reports/msb2*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </span>
                                <span>MSB2 Report</span>
                            </a>
                            <a href="{{ route('reports.lctr') }}" class="nav-link {{ request()->is('reports/lctr*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                </span>
                                <span>LCTR</span>
                            </a>
                            <a href="{{ route('reports.lmca') }}" class="nav-link {{ request()->is('reports/lmca*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                </span>
                                <span>LMCA</span>
                            </a>
                            <a href="{{ route('reports.quarterly-lvr') }}" class="nav-link {{ request()->is('reports/quarterly-lvr*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                </span>
                                <span>Quarterly LVR</span>
                            </a>
                            <a href="{{ route('reports.position-limit') }}" class="nav-link {{ request()->is('reports/position-limit*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                </span>
                                <span>Position Limits</span>
                            </a>
                            <a href="{{ route('reports.history') }}" class="nav-link {{ request()->is('reports/history*') ? 'active' : '' }}">
                                <span class="nav-icon">
                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </span>
                                <span>Report History</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ============================================================
                    SYSTEM - Administrative tasks
                ============================================================ --}}
                <div class="nav-section">
                    <div class="nav-section-label">System</div>

                    <div class="nav-item">
                        <a href="{{ route('tasks.index') }}" class="nav-link {{ request()->is('tasks*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            </span>
                            <span>Tasks</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="{{ route('audit.index') }}" class="nav-link {{ request()->is('audit*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            </span>
                            <span>Audit Log</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->is('users*') ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </span>
                            <span>Users</span>
                        </a>
                    </div>
                </div>
            </nav>

            <div class="sidebar-footer">
                <form id="logout-form" action="/logout" method="POST">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        </span>
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

    <script>
        // Mobile-friendly dropdown toggle with click
        document.querySelectorAll('.nav-group > .nav-link').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                var navGroup = this.parentElement;
                if (navGroup.classList.contains('nav-group')) {
                    // Check if we're on the parent link or just toggling
                    var href = this.getAttribute('href');
                    var isParentLink = href && !href.includes('#');

                    // Only toggle if clicking on the arrow or if already open
                    if (navGroup.classList.contains('open') || e.target.closest('.nav-arrow')) {
                        e.preventDefault();
                        navGroup.classList.toggle('open');
                    }
                }
            });
        });
    </script>

    @yield('scripts')
</body>
</html>
