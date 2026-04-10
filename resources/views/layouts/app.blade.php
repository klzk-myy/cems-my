<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CEMS-MY')</title>
</head>
<body class="antialiased font-sans bg-gray-100 text-gray-800 m-0 p-0">
<div class="flex min-h-screen">
<!-- Left Sidebar - Corporate Professional -->
<aside class="sidebar">
    <div class="sidebar__header">
        <div class="sidebar__logo">
            <span class="sidebar__logo-icon">CEMS</span>
            <span>CEMS-MY</span>
        </div>
        <p class="sidebar__tagline">Currency Exchange MSB</p>
    </div>

    <nav class="sidebar__nav" role="navigation" aria-label="Main menu">
        {{-- Operations --}}
        <div class="sidebar__group">
            <div class="sidebar__group-title">Operations</div>

            <a href="{{ route('dashboard') }}"
               class="sidebar__link {{ request()->is('dashboard') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('transactions.index') }}"
               class="sidebar__link {{ request()->is('transactions*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 7h12M8 12h12M8 17h12M4 7h.01M4 12h.01M4 17h.01"/>
                </svg>
                <span>Transactions</span>
            </a>

            <a href="{{ route('customers.index') }}"
               class="sidebar__link {{ request()->is('customers*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Customers</span>
            </a>
        </div>

        {{-- Counter Management --}}
        <div class="sidebar__group">
            <div class="sidebar__group-title">Counter Management</div>

            <a href="{{ route('counters.index') }}"
               class="sidebar__link {{ request()->is('counters*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="M6 8h.01M6 12h.01M6 16h.01M10 8h8M10 12h8M10 16h8"/>
                </svg>
                <span>Counters</span>
            </a>

            <a href="{{ route('branches.index') }}"
               class="sidebar__link {{ request()->is('branches*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Branches</span>
            </a>
        </div>

        {{-- Stock Management --}}
        <div class="sidebar__group">
            <div class="sidebar__group-title">Stock Management</div>

            <a href="{{ route('stock-cash.index') }}"
               class="sidebar__link {{ request()->is('stock-cash*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                <span>Stock & Cash</span>
            </a>

            <a href="{{ route('stock-transfers.index') }}"
               class="sidebar__link {{ request()->is('stock-transfers*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>Stock Transfers</span>
            </a>
        </div>

        {{-- Compliance & AML --}}
        <div class="sidebar__group">
            <div class="sidebar__group-title">Compliance & AML</div>

            <a href="{{ route('compliance') }}"
               class="sidebar__link {{ request()->is('compliance') && !request()->is('compliance/*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span>Compliance</span>
            </a>

            <a href="{{ route('compliance.alerts.index') }}"
               class="sidebar__link {{ request()->is('compliance/alerts*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span>Alert Triage</span>
            </a>

            <a href="{{ route('compliance.workspace') }}"
               class="sidebar__link {{ request()->is('compliance/workspace*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
                </svg>
                <span>Compliance Workspace</span>
            </a>

            <a href="{{ route('compliance.edd.index') }}"
               class="sidebar__link {{ request()->is('compliance/edd*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                </svg>
                <span>EDD Records</span>
            </a>

            <a href="{{ route('compliance.risk-dashboard.index') }}"
               class="sidebar__link {{ request()->is('compliance/risk-dashboard*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/>
                </svg>
                <span>Risk Dashboard</span>
            </a>

            <a href="{{ route('compliance.str-studio.index') }}"
               class="sidebar__link {{ request()->is('compliance/str-studio*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                <span>STR Studio</span>
            </a>

            <a href="{{ route('compliance.cases.index') }}"
               class="sidebar__link {{ request()->is('compliance/cases*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                </svg>
                <span>Cases</span>
            </a>

            <a href="{{ route('str.index') }}"
               class="sidebar__link {{ request()->is('str*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>STR Reports</span>
            </a>
        </div>

        {{-- Accounting --}}
        <div class="sidebar__group">
            <div class="sidebar__group-title">Accounting</div>

            <a href="{{ route('accounting.index') }}"
               class="sidebar__link {{ request()->is('accounting') && !request()->is('accounting/*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="2" width="16" height="20" rx="2"/>
                    <line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/>
                    <line x1="8" y1="14" x2="12" y2="14"/>
                </svg>
                <span>Accounting</span>
            </a>

            <a href="{{ route('accounting.journal') }}"
               class="sidebar__link {{ request()->is('accounting/journal*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                <span>Journal</span>
            </a>

            <a href="{{ route('accounting.ledger') }}"
               class="sidebar__link {{ request()->is('accounting/ledger*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
                <span>Ledger</span>
            </a>

            <a href="{{ route('accounting.trial-balance') }}"
               class="sidebar__link {{ request()->is('accounting/trial-balance*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 9h18M3 15h18"/>
                </svg>
                <span>Trial Balance</span>
            </a>

            <a href="{{ route('accounting.profit-loss') }}"
               class="sidebar__link {{ request()->is('accounting/profit-loss*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 9h18M3 15h18"/>
                </svg>
                <span>Profit & Loss</span>
            </a>

            <a href="{{ route('accounting.balance-sheet') }}"
               class="sidebar__link {{ request()->is('accounting/balance-sheet*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 9h18M3 15h18"/>
                </svg>
                <span>Balance Sheet</span>
            </a>

            <a href="{{ route('accounting.cash-flow') }}"
               class="sidebar__link {{ request()->is('accounting/cash-flow*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 9h18M3 15h18"/>
                </svg>
                <span>Cash Flow</span>
            </a>

            <a href="{{ route('accounting.ratios') }}"
               class="sidebar__link {{ request()->is('accounting/ratios*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 9h18M3 15h18"/>
                </svg>
                <span>Financial Ratios</span>
            </a>

            <a href="{{ route('accounting.revaluation') }}"
               class="sidebar__link {{ request()->is('accounting/revaluation*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 9h18M3 15h18"/>
                </svg>
                <span>Revaluation</span>
            </a>

            <a href="{{ route('accounting.reconciliation') }}"
               class="sidebar__link {{ request()->is('accounting/reconciliation*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 9h18M3 15h18"/>
                </svg>
                <span>Reconciliation</span>
            </a>

            <a href="{{ route('accounting.budget') }}"
               class="sidebar__link {{ request()->is('accounting/budget*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 9h18M3 15h18"/>
                </svg>
                <span>Budget</span>
            </a>

            <a href="{{ route('accounting.periods') }}"
               class="sidebar__link {{ request()->is('accounting/periods*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 9h18M3 15h18"/>
                </svg>
                <span>Periods</span>
            </a>

            <a href="{{ route('accounting.fiscal-years') }}"
               class="sidebar__link {{ request()->is('accounting/fiscal-years*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18M3 9h18M3 15h18"/>
                </svg>
                <span>Fiscal Years</span>
            </a>
        </div>

        {{-- Reports --}}
        <div class="sidebar__group">
            <div class="sidebar__group-title">Reports</div>

            <a href="{{ route('reports.index') }}"
               class="sidebar__link {{ request()->is('reports') && !request()->is('reports/*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>Reports</span>
            </a>

            <a href="{{ route('reports.msb2') }}"
               class="sidebar__link {{ request()->is('reports/msb2*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>MSB2 Report</span>
            </a>

            <a href="{{ route('reports.lctr') }}"
               class="sidebar__link {{ request()->is('reports/lctr*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>LCTR</span>
            </a>

            <a href="{{ route('reports.lmca') }}"
               class="sidebar__link {{ request()->is('reports/lmca*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>LMCA</span>
            </a>

            <a href="{{ route('reports.quarterly-lvr') }}"
               class="sidebar__link {{ request()->is('reports/quarterly-lvr*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>Quarterly LVR</span>
            </a>

            <a href="{{ route('reports.position-limit') }}"
               class="sidebar__link {{ request()->is('reports/position-limit*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>Position Limits</span>
            </a>

            <a href="{{ route('reports.history') }}"
               class="sidebar__link {{ request()->is('reports/history*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span>Report History</span>
            </a>
        </div>

        {{-- System --}}
        <div class="sidebar__group">
            <div class="sidebar__group-title">System</div>

            <a href="{{ route('audit.index') }}"
               class="sidebar__link {{ request()->is('audit*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                <span>Audit Log</span>
            </a>

            <a href="{{ route('users.index') }}"
               class="sidebar__link {{ request()->is('users*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Users</span>
            </a>

            <a href="{{ route('tasks.index') }}"
               class="sidebar__link {{ request()->is('tasks*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <span>Tasks</span>
            </a>

            <a href="{{ route('transactions.batch-upload') }}"
               class="sidebar__link {{ request()->is('transactions/batch-upload*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                <span>Transaction Imports</span>
            </a>

            <a href="{{ route('data-breach-alerts.index') }}"
               class="sidebar__link {{ request()->is('data-breach-alerts*') ? 'sidebar__link--active' : '' }}">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span>Data Breach Alerts</span>
            </a>
        </div>
    </nav>

    <div class="sidebar__footer">
        <form id="logout-form" action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="sidebar__logout" style="width:100%; background:none; border:none; cursor:pointer;">
                <svg class="sidebar__link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<!-- Main Content -->
<main class="main-content" role="main" aria-label="Main content" style="margin-left: var(--sidebar-width); min-height: 100vh; background: var(--color-gray-50);">
    <div style="padding: 2rem; max-width: 1400px; margin: 0 auto;">
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-r-lg" role="alert" aria-live="polite">{{ e(session('success')) }}</div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-r-lg" role="alert" aria-live="assertive">{{ e(session('error')) }}</div>
        @endif

        @if(session('warning'))
            <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 mb-4 rounded-r-lg" role="alert" aria-live="polite">{{ e(session('warning')) }}</div>
        @endif

        @yield('content')
    </div>

    <footer class="text-center py-6 text-gray-500 text-sm border-t border-gray-200">
        <p>CEMS-MY v1.0 - Bank Negara Malaysia Compliant MSB Management System</p>
    </footer>
</main>
</div>


@yield('scripts')
</body>
</html>
