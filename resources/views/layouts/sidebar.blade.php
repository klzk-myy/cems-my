@extends('layouts.base')

@section('sidebar')
<!-- Modern Sidebar Navigation -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h1>CEMS-MY</h1>
        <p>Currency Exchange MSB</p>
    </div>

    <nav class="nav">
        
        {{-- ============================================================
        MAIN NAVIGATION
        ============================================================ --}}
        
        {{-- Dashboard --}}
        <div class="nav-section">
            <div class="nav-section-label">Main</div>
            
            <div class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    </span>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        {{-- ============================================================
        OPERATIONS
        ============================================================ --}}
        <div class="nav-section">
            <div class="nav-section-label">Operations</div>
            
            <div class="nav-item">
                <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->is('transactions*') && !request()->is('transactions/batch-upload*') ? 'active' : '' }}">
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

            {{-- Counter Management Group --}}
            <div class="nav-group">
                <a href="#" class="nav-link {{ request()->is('counters*') || request()->is('branches*') ? 'active' : '' }}" onclick="event.preventDefault(); toggleNav(this);">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h.01M6 12h.01M6 16h.01M10 8h8M10 12h8M10 16h8"/></svg>
                    </span>
                    <span>Counter Management</span>
                    <span class="nav-arrow">▼</span>
                </a>
                <div class="nav-submenu">
                    <a href="{{ route('counters.index') }}" class="nav-link {{ request()->is('counters*') ? 'active' : '' }}">
                        <span>Counters</span>
                    </a>
                    <a href="{{ route('branches.index') }}" class="nav-link {{ request()->is('branches*') ? 'active' : '' }}">
                        <span>Branches</span>
                    </a>
                </div>
            </div>

            {{-- Stock Management Group --}}
            <div class="nav-group">
                <a href="#" class="nav-link {{ request()->is('stock-cash*') || request()->is('stock-transfers*') ? 'active' : '' }}" onclick="event.preventDefault(); toggleNav(this);">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </span>
                    <span>Stock Management</span>
                    <span class="nav-arrow">▼</span>
                </a>
                <div class="nav-submenu">
                    <a href="{{ route('stock-cash.index') }}" class="nav-link {{ request()->is('stock-cash*') ? 'active' : '' }}">
                        <span>Stock & Cash</span>
                    </a>
                    <a href="{{ route('stock-transfers.index') }}" class="nav-link {{ request()->is('stock-transfers*') ? 'active' : '' }}">
                        <span>Stock Transfers</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================================================
        COMPLIANCE & AML
        ============================================================ --}}
        <div class="nav-section">
            <div class="nav-section-label">Compliance & AML</div>
            
            <div class="nav-group">
                <a href="#" class="nav-link {{ request()->is('compliance*') || request()->is('str*') ? 'active' : '' }}" onclick="event.preventDefault(); toggleNav(this);">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </span>
                    <span>Compliance</span>
                    <span class="nav-arrow">▼</span>
                </a>
                <div class="nav-submenu">
                    <a href="{{ route('compliance') }}" class="nav-link {{ request()->is('compliance') && !request()->is('compliance/*') ? 'active' : '' }}">
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('compliance.workspace') }}" class="nav-link {{ request()->is('compliance/workspace*') ? 'active' : '' }}">
                        <span>Workspace</span>
                    </a>
                    <a href="{{ route('compliance.alerts.index') }}" class="nav-link {{ request()->is('compliance/alerts*') ? 'active' : '' }}">
                        <span>Alert Triage</span>
                    </a>
                    <a href="{{ route('compliance.cases.index') }}" class="nav-link {{ request()->is('compliance/cases*') ? 'active' : '' }}">
                        <span>Cases</span>
                    </a>
                    <a href="{{ route('compliance.flagged') }}" class="nav-link {{ request()->is('compliance/flagged*') ? 'active' : '' }}">
                        <span>Flagged</span>
                    </a>
                    <a href="{{ route('compliance.edd.index') }}" class="nav-link {{ request()->is('compliance/edd*') && !request()->is('compliance/edd-templates*') ? 'active' : '' }}">
                        <span>EDD Records</span>
                    </a>
                    <a href="{{ route('compliance.edd-templates.index') }}" class="nav-link {{ request()->is('compliance/edd-templates*') ? 'active' : '' }}">
                        <span>EDD Templates</span>
                    </a>
                    <a href="{{ route('compliance.rules.index') }}" class="nav-link {{ request()->is('compliance/rules*') ? 'active' : '' }}">
                        <span>AML Rules</span>
                    </a>
                    <a href="{{ route('compliance.risk-dashboard.index') }}" class="nav-link {{ request()->is('compliance/risk-dashboard*') ? 'active' : '' }}">
                        <span>Risk Dashboard</span>
                    </a>
                    <a href="{{ route('compliance.str-studio.index') }}" class="nav-link {{ request()->is('compliance/str-studio*') ? 'active' : '' }}">
                        <span>STR Studio</span>
                    </a>
                    <a href="{{ route('str.index') }}" class="nav-link {{ request()->is('str*') ? 'active' : '' }}">
                        <span>STR Reports</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================================================
        ACCOUNTING
        ============================================================ --}}
        <div class="nav-section">
            <div class="nav-section-label">Accounting</div>
            
            <div class="nav-group">
                <a href="#" class="nav-link {{ request()->is('accounting*') ? 'active' : '' }}" onclick="event.preventDefault(); toggleNav(this);">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>
                    </span>
                    <span>Accounting</span>
                    <span class="nav-arrow">▼</span>
                </a>
                <div class="nav-submenu">
                    <a href="{{ route('accounting.index') }}" class="nav-link {{ request()->is('accounting') && !request()->is('accounting/*') ? 'active' : '' }}">
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('accounting.journal') }}" class="nav-link {{ request()->is('accounting/journal*') ? 'active' : '' }}">
                        <span>Journal Entries</span>
                    </a>
                    <a href="{{ route('accounting.ledger') }}" class="nav-link {{ request()->is('accounting/ledger*') ? 'active' : '' }}">
                        <span>Ledger</span>
                    </a>
                    <a href="{{ route('accounting.trial-balance') }}" class="nav-link {{ request()->is('accounting/trial-balance*') ? 'active' : '' }}">
                        <span>Trial Balance</span>
                    </a>
                    <a href="{{ route('accounting.profit-loss') }}" class="nav-link {{ request()->is('accounting/profit-loss*') ? 'active' : '' }}">
                        <span>Profit & Loss</span>
                    </a>
                    <a href="{{ route('accounting.balance-sheet') }}" class="nav-link {{ request()->is('accounting/balance-sheet*') ? 'active' : '' }}">
                        <span>Balance Sheet</span>
                    </a>
                    <a href="{{ route('accounting.cash-flow') }}" class="nav-link {{ request()->is('accounting/cash-flow*') ? 'active' : '' }}">
                        <span>Cash Flow</span>
                    </a>
                    <a href="{{ route('accounting.ratios') }}" class="nav-link {{ request()->is('accounting/ratios*') ? 'active' : '' }}">
                        <span>Financial Ratios</span>
                    </a>
                    <a href="{{ route('accounting.revaluation') }}" class="nav-link {{ request()->is('accounting/revaluation*') ? 'active' : '' }}">
                        <span>Revaluation</span>
                    </a>
                    <a href="{{ route('accounting.reconciliation') }}" class="nav-link {{ request()->is('accounting/reconciliation*') ? 'active' : '' }}">
                        <span>Reconciliation</span>
                    </a>
                    <a href="{{ route('accounting.budget') }}" class="nav-link {{ request()->is('accounting/budget*') ? 'active' : '' }}">
                        <span>Budget</span>
                    </a>
                    <a href="{{ route('accounting.periods') }}" class="nav-link {{ request()->is('accounting/periods*') ? 'active' : '' }}">
                        <span>Periods</span>
                    </a>
                    <a href="{{ route('accounting.fiscal-years') }}" class="nav-link {{ request()->is('accounting/fiscal-years*') ? 'active' : '' }}">
                        <span>Fiscal Years</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================================================
        REPORTS
        ============================================================ --}}
        <div class="nav-section">
            <div class="nav-section-label">Reports</div>
            
            <div class="nav-group">
                <a href="#" class="nav-link {{ request()->is('reports*') ? 'active' : '' }}" onclick="event.preventDefault(); toggleNav(this);">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    </span>
                    <span>Reports</span>
                    <span class="nav-arrow">▼</span>
                </a>
                <div class="nav-submenu">
                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->is('reports') && !request()->is('reports/*') ? 'active' : '' }}">
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('reports.msb2') }}" class="nav-link {{ request()->is('reports/msb2*') ? 'active' : '' }}">
                        <span>MSB2 Report</span>
                    </a>
                    <a href="{{ route('reports.lctr') }}" class="nav-link {{ request()->is('reports/lctr*') ? 'active' : '' }}">
                        <span>LCTR</span>
                    </a>
                    <a href="{{ route('reports.lmca') }}" class="nav-link {{ request()->is('reports/lmca*') ? 'active' : '' }}">
                        <span>LMCA</span>
                    </a>
                    <a href="{{ route('reports.quarterly-lvr') }}" class="nav-link {{ request()->is('reports/quarterly-lvr*') ? 'active' : '' }}">
                        <span>Quarterly LVR</span>
                    </a>
                    <a href="{{ route('reports.position-limit') }}" class="nav-link {{ request()->is('reports/position-limit*') ? 'active' : '' }}">
                        <span>Position Limits</span>
                    </a>
                    <a href="{{ route('reports.history') }}" class="nav-link {{ request()->is('reports/history*') ? 'active' : '' }}">
                        <span>Report History</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================================================
        SYSTEM
        ============================================================ --}}
        <div class="nav-section">
            <div class="nav-section-label">System</div>
            
            <div class="nav-group">
                <a href="#" class="nav-link {{ request()->is('tasks*') || request()->is('transactions/batch-upload*') || request()->is('audit*') || request()->is('users*') || request()->is('data-breach-alerts*') ? 'active' : '' }}" onclick="event.preventDefault(); toggleNav(this);">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </span>
                    <span>Settings</span>
                    <span class="nav-arrow">▼</span>
                </a>
                <div class="nav-submenu">
                    <a href="{{ route('tasks.index') }}" class="nav-link {{ request()->is('tasks*') ? 'active' : '' }}">
                        <span>Tasks</span>
                    </a>
                    <a href="{{ route('transactions.batch-upload') }}" class="nav-link {{ request()->is('transactions/batch-upload*') ? 'active' : '' }}">
                        <span>Transaction Imports</span>
                    </a>
                    <a href="{{ route('audit.index') }}" class="nav-link {{ request()->is('audit*') ? 'active' : '' }}">
                        <span>Audit Log</span>
                    </a>
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->is('users*') ? 'active' : '' }}">
                        <span>Users</span>
                    </a>
                    <a href="{{ route('data-breach-alerts.index') }}" class="nav-link {{ request()->is('data-breach-alerts*') ? 'active' : '' }}">
                        <span>Data Breach Alerts</span>
                    </a>
                </div>
            </div>
        </div>

    </nav>

    {{-- Logout --}}
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

<script>
function toggleNav(element) {
    var navGroup = element.parentElement;
    if (navGroup.classList.contains('nav-group')) {
        navGroup.classList.toggle('open');
        var arrow = element.querySelector('.nav-arrow');
        if (arrow) {
            arrow.textContent = navGroup.classList.contains('open') ? '▲' : '▼';
        }
    }
}
</script>
@endsection
