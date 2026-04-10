# CEMS-MY Frontend Theme Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement Corporate Professional theme across all CEMS-MY views with deep navy sidebar, Merriweather + Source Sans typography, and Royal Blue accents.

**Architecture:** Layered CSS approach — Tailwind v4 utilities via `@import "tailwindcss"`, custom component classes in `app.css` for complex components, Google Fonts for typography. Layout handled in `layouts/app.blade.php` with 240px fixed sidebar.

**Tech Stack:** Laravel 10.x Blade, Tailwind CSS v4, Google Fonts (Merriweather, Source Sans 3, JetBrains Mono)

---

## File Structure

```
resources/
├── css/
│   └── app.css          # Theme variables, component classes (MODIFY)
└── views/
    ├── layouts/
    │   └── app.blade.php  # Sidebar, navigation, layout shell (MODIFY)
    ├── dashboard.blade.php (MODIFY)
    ├── transactions/
    │   ├── index.blade.php (MODIFY)
    │   ├── create.blade.php (MODIFY)
    │   ├── show.blade.php (MODIFY)
    │   └── customer-history.blade.php (MODIFY)
    ├── customers/
    │   ├── index.blade.php (MODIFY)
    │   ├── create.blade.php (MODIFY)
    │   ├── show.blade.php (MODIFY)
    │   └── edit.blade.php (MODIFY)
    ├── compliance/       # ~15 views (MODIFY)
    ├── accounting/      # ~15 views (MODIFY)
    ├── auth/
    │   └── login.blade.php (MODIFY)
    ├── errors/
    │   ├── 403.blade.php (MODIFY)
    │   ├── 404.blade.php (MODIFY)
    │   └── 500.blade.php (MODIFY)
    └── ...              # Remaining views (MODIFY)
```

---

## Task 1: CSS Theme Foundation

**Files:**
- Modify: `resources/css/app.css:1-50`

- [ ] **Step 1: Add Google Fonts import and theme variables**

```css
/* ========================================
   GOOGLE FONTS
   ======================================== */
@import url('https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Source+Sans+3:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

/* ========================================
   THEME VARIABLES - Corporate Professional
   ======================================== */
:root {
  /* Primary - Deep Navy */
  --color-primary: #1a365d;
  --color-primary-light: #2c5282;
  --color-primary-lighter: #3182ce;
  
  /* Accent - Classic Gold */
  --color-gold: #D4AF37;
  --color-gold-light: #e6c068;
  
  /* Semantic */
  --color-success: #38a169;
  --color-warning: #dd6b20;
  --color-danger: #e53e3e;
  
  /* Grays */
  --color-gray-50: #f7fafc;
  --color-gray-100: #edf2f7;
  --color-gray-200: #e2e8f0;
  --color-gray-300: #cbd5e0;
  --color-gray-400: #a0aec0;
  --color-gray-500: #718096;
  --color-gray-600: #4a5568;
  --color-gray-700: #2d3748;
  --color-gray-800: #1a202c;
  
  /* Typography */
  --font-heading: 'Merriweather', Georgia, serif;
  --font-body: 'Source Sans 3', -apple-system, sans-serif;
  --font-mono: 'JetBrains Mono', 'Fira Code', monospace;
  
  /* Spacing */
  --sidebar-width: 240px;
  
  /* Border radius */
  --radius-sm: 4px;
  --radius-md: 6px;
  --radius-lg: 8px;
  --radius-xl: 12px;
  --radius-2xl: 16px;
  --radius-full: 9999px;
  
  /* Shadows */
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
  --shadow-xl: 0 20px 25px rgba(0,0,0,0.15);
}
```

- [ ] **Step 2: Run test to verify syntax**

Run: `php artisan test --filter=TestSomething 2>&1 | head -20`
Expected: No PHP errors (this is CSS only)

- [ ] **Step 3: Add base body styles**

```css
/* ========================================
   BASE STYLES
   ======================================== */
body {
  font-family: var(--font-body);
  background: var(--color-gray-50);
  color: var(--color-gray-800);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-heading);
  font-weight: 700;
  color: var(--color-gray-800);
  line-height: 1.3;
}

/* Scrollbar */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--color-gray-100); }
::-webkit-scrollbar-thumb { background: var(--color-gray-300); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--color-gray-400); }
::selection { background: #3182ce; color: #fff; }
```

- [ ] **Step 4: Update component classes - Cards**

Find the existing `.card` class in `app.css` and replace with:

```css
/* ========================================
   CARDS
   ======================================== */
.card {
  background: white;
  border-radius: var(--radius-xl);
  padding: 1.5rem;
  box-shadow: var(--shadow-sm);
  margin-bottom: 1.5rem;
  transition: box-shadow 200ms ease;
}
.card:hover {
  box-shadow: var(--shadow-md);
}
.card--bordered {
  border: 1px solid var(--color-gray-200);
}
.card--featured {
  border-top: 3px solid var(--color-gold);
}
.card--hover:hover {
  border-color: var(--color-primary-lighter);
  box-shadow: var(--shadow-lg);
}
```

- [ ] **Step 5: Update component classes - Buttons**

Replace existing `.btn` classes:

```css
/* ========================================
   BUTTONS
   ======================================== */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border-radius: var(--radius-md);
  font-family: var(--font-body);
  font-weight: 600;
  font-size: 0.875rem;
  text-decoration: none;
  cursor: pointer;
  border: none;
  transition: all 150ms ease;
}
.btn:focus {
  outline: 2px solid var(--color-primary-lighter);
  outline-offset: 2px;
}
.btn--primary {
  background: var(--color-primary);
  color: white;
}
.btn--primary:hover {
  background: var(--color-primary-light);
}
.btn--success {
  background: var(--color-success);
  color: white;
}
.btn--success:hover {
  background: #2f855a;
}
.btn--danger {
  background: var(--color-danger);
  color: white;
}
.btn--danger:hover {
  background: #c53030;
}
.btn--warning {
  background: var(--color-warning);
  color: white;
}
.btn--warning:hover {
  background: #c05621;
}
.btn--secondary {
  background: var(--color-gray-200);
  color: var(--color-gray-700);
}
.btn--secondary:hover {
  background: var(--color-gray-300);
}
.btn--ghost {
  background: transparent;
  color: var(--color-primary);
}
.btn--ghost:hover {
  background: var(--color-gray-100);
}
.btn--sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}
.btn--lg {
  padding: 0.875rem 1.75rem;
  font-size: 1rem;
}
.btn--full {
  width: 100%;
}
```

- [ ] **Step 6: Update component classes - Tables**

```css
/* ========================================
   DATA TABLES
   ======================================== */
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}
.data-table thead {
  background: var(--color-gray-50);
}
.data-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-gray-500);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 2px solid var(--color-gray-200);
}
.data-table td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--color-gray-100);
  color: var(--color-gray-700);
}
.data-table tbody tr:hover td {
  background: #ebf8ff;
}
.data-table tbody tr:nth-child(even) td {
  background: var(--color-gray-50);
}
.data-table tbody tr:nth-child(even):hover td {
  background: #ebf8ff;
}
```

- [ ] **Step 7: Update component classes - Status Badges**

```css
/* ========================================
   STATUS BADGES
   ======================================== */
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 600;
}
.status-badge--completed,
.status-badge.status-completed {
  background: #c6f6d5;
  color: #276749;
}
.status-badge--pending,
.status-badge.status-pending {
  background: #feebc8;
  color: #c05621;
}
.status-badge--flagged,
.status-badge.status-flagged,
.status-badge--onhold {
  background: #fed7d7;
  color: #c53030;
}
.status-badge--draft {
  background: var(--color-gray-100);
  color: var(--color-gray-600);
}
.status-badge--active {
  background: #c6f6d5;
  color: #276749;
}
.status-badge--inactive {
  background: var(--color-gray-200);
  color: var(--color-gray-500);
}
```

- [ ] **Step 8: Update component classes - Forms**

```css
/* ========================================
   FORMS
   ======================================== */
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
.form-group label .required {
  color: var(--color-gold);
  margin-left: 2px;
}
.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid var(--color-gray-200);
  border-radius: var(--radius-md);
  font-family: var(--font-body);
  font-size: 0.875rem;
  color: var(--color-gray-800);
  background: white;
  transition: border-color 150ms ease;
}
.form-input:focus {
  outline: none;
  border-color: var(--color-primary-lighter);
  box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
}
.form-input::placeholder {
  color: var(--color-gray-400);
}
.form-input--error {
  border-color: var(--color-danger);
}
.form-select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid var(--color-gray-200);
  border-radius: var(--radius-md);
  font-family: var(--font-body);
  font-size: 0.875rem;
  color: var(--color-gray-800);
  background: white;
  cursor: pointer;
  transition: border-color 150ms ease;
}
.form-select:focus {
  outline: none;
  border-color: var(--color-primary-lighter);
}
.form-error {
  display: block;
  margin-top: 0.375rem;
  font-size: 0.75rem;
  color: var(--color-danger);
}
.form-help {
  display: block;
  margin-top: 0.375rem;
  font-size: 0.75rem;
  color: var(--color-gray-500);
}
```

- [ ] **Step 9: Update component classes - Navigation Sidebar**

```css
/* ========================================
   SIDEBAR NAVIGATION
   ======================================== */
.sidebar {
  width: var(--sidebar-width);
  background: linear-gradient(180deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
  color: white;
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 50;
  overflow-y: auto;
}
.sidebar__header {
  padding: 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}
.sidebar__logo {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 700;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.sidebar__logo-icon {
  width: 32px;
  height: 32px;
  background: var(--color-gold);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
}
.sidebar__tagline {
  font-size: 0.625rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: rgba(255,255,255,0.5);
  margin-top: 0.25rem;
}
.sidebar__nav {
  flex: 1;
  padding: 1rem 0;
  overflow-y: auto;
}
.sidebar__group {
  margin-bottom: 0.5rem;
}
.sidebar__group-title {
  padding: 0.5rem 1.5rem;
  font-size: 0.625rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: rgba(255,255,255,0.4);
}
.sidebar__link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  color: rgba(255,255,255,0.8);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 150ms ease;
  border-left: 3px solid transparent;
}
.sidebar__link:hover {
  background: rgba(255,255,255,0.1);
  color: white;
}
.sidebar__link--active {
  background: rgba(255,255,255,0.1);
  color: white;
  border-left-color: var(--color-gold);
}
.sidebar__link-icon {
  width: 20px;
  height: 20px;
  opacity: 0.7;
}
.sidebar__link--active .sidebar__link-icon {
  opacity: 1;
}
.sidebar__footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid rgba(255,255,255,0.1);
}
.sidebar__logout {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: rgba(255,255,255,0.1);
  color: white;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: var(--radius-md);
  transition: background 150ms ease;
}
.sidebar__logout:hover {
  background: rgba(255,255,255,0.2);
}
```

- [ ] **Step 10: Commit**

```bash
git add resources/css/app.css
git commit -m "feat(theme): add Corporate Professional CSS foundation

- Add Google Fonts (Merriweather, Source Sans 3, JetBrains Mono)
- Add CSS custom properties for color palette, typography, spacing
- Update card component with gold accent option
- Update buttons with primary/success/danger/warning variants
- Update data tables with zebra striping and hover states
- Update status badges with semantic colors
- Update forms with styled inputs and labels
- Add sidebar navigation component styles"
```

---

## Task 2: Layout Shell - Sidebar & App Structure

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Read existing layout**

```bash
head -100 resources/views/layouts/app.blade.php
```

- [ ] **Step 2: Replace sidebar with Corporate Professional version**

Replace the existing `<aside>` section (lines ~11-416) with:

```blade
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
```

- [ ] **Step 3: Update main content area classes**

Replace the main content wrapper class `ml-60` with a CSS variable approach and update padding:

```blade
<!-- Main Content -->
<main class="main-content" role="main" aria-label="Main content" 
      style="margin-left: var(--sidebar-width); min-height: 100vh; background: var(--color-gray-50);">
    <div class="content-wrapper" style="padding: 2rem; max-width: 1400px; margin: 0 auto;">
```

- [ ] **Step 4: Update page header styles**

Add a page header component style block after `<body>`:

```blade
<style>
.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--color-gray-200);
}
.page-header__title {
    font-family: var(--font-heading);
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-gray-800);
    margin-bottom: 0.5rem;
}
.page-header__subtitle {
    font-size: 0.875rem;
    color: var(--color-gray-500);
}
.page-header__actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
}
</style>
```

- [ ] **Step 5: Add page header macro to layout (optional, can use in pages)**

Add before `</head>` in the layout:

```blade
@php
    function page_header($title, $subtitle = '', $actions = '') {
        return sprintf(
            '<div class="page-header"><h1 class="page-header__title">%s</h1>%s%s</div>',
            $title,
            $subtitle ? sprintf('<p class="page-header__subtitle">%s</p>', $subtitle) : '',
            $actions ? sprintf('<div class="page-header__actions">%s</div>', $actions) : ''
        );
    }
@endphp
```

Actually, for Blade layouts, just use the classes directly in each page. No macro needed.

- [ ] **Step 6: Run tests to verify layout loads**

Run: `php artisan route:list --path=dashboard 2>&1 | head -10`
Expected: Dashboard route listed

- [ ] **Step 7: Commit**

```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat(layout): apply Corporate Professional sidebar

- Replace sidebar with deep navy gradient design
- Add gold accent on logo icon
- Simplify navigation groups with icons
- Add sidebar__link--active state with gold border
- Update main content margin for fixed sidebar
- Add page-header component styles"
```

---

## Task 3: Dashboard Theme

**Files:**
- Modify: `resources/views/dashboard.blade.php`

- [ ] **Step 1: Read dashboard file**

```bash
cat resources/views/dashboard.blade.php
```

- [ ] **Step 2: Update stats cards with Corporate Professional styling**

Replace the stats grid (lines ~12-29):

```blade
<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $stats['total_transactions'] ?? 0 }}</div>
        <div class="stat-card__label">Total Transactions</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ number_format($stats['buy_volume'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Buy Volume (MYR)</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ number_format($stats['sell_volume'] ?? 0, 2) }}</div>
        <div class="stat-card__label">Sell Volume (MYR)</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $stats['flagged'] ?? 0 }}</div>
        <div class="stat-card__label">Flagged Transactions</div>
    </div>
</div>
```

- [ ] **Step 3: Add stat-card CSS to app.css**

```css
/* ========================================
   STAT CARDS
   ======================================== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.stat-card {
  background: white;
  border-radius: var(--radius-xl);
  padding: 1.5rem;
  text-align: center;
  box-shadow: var(--shadow-sm);
  transition: all 200ms ease;
}
.stat-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}
.stat-card__value {
  font-family: var(--font-mono);
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}
.stat-card__label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-gray-500);
}
.stat-card--primary .stat-card__value { color: var(--color-primary); }
.stat-card--success .stat-card__value { color: var(--color-success); }
.stat-card--warning .stat-card__value { color: var(--color-warning); }
.stat-card--danger .stat-card__value { color: var(--color-danger); }
.stat-card--primary { border-top: 3px solid var(--color-primary); }
.stat-card--success { border-top: 3px solid var(--color-success); }
.stat-card--warning { border-top: 3px solid var(--color-warning); }
.stat-card--danger { border-top: 3px solid var(--color-danger); }
```

- [ ] **Step 4: Update page title section**

```blade
<div class="page-header">
    <h1 class="page-header__title">Welcome to CEMS-MY</h1>
    <p class="page-header__subtitle">Bank Negara Malaysia Compliant Currency Exchange Management System</p>
</div>
```

- [ ] **Step 5: Update quick action buttons**

```blade
<div class="quick-actions">
    <a href="{{ route('transactions.create') }}" class="btn btn--success btn--lg btn--full">+ New Transaction</a>
    <a href="{{ route('customers.create') }}" class="btn btn--primary btn--lg btn--full">Register Customer</a>
    <a href="{{ route('compliance.flagged') }}" class="btn btn--warning btn--lg btn--full">View Flagged ({{ $stats['flagged'] ?? 0 }})</a>
</div>
```

- [ ] **Step 6: Update table styling**

Use the `.data-table` class and ensure proper header structure.

- [ ] **Step 7: Run visual test**

Run: `php artisan serve --host=localhost --port=8000 2>&1 &` then check http://localhost:8000/dashboard

- [ ] **Step 8: Commit**

```bash
git add resources/views/dashboard.blade.php
git commit -m "feat(dashboard): apply Corporate Professional theme

- Update stats cards with border-top accents
- Add stat-card CSS with hover effects
- Use page-header component
- Update quick action buttons"
```

---

## Task 4: Transactions Index

**Files:**
- Modify: `resources/views/transactions/index.blade.php`

- [ ] **Step 1: Read file**

```bash
cat resources/views/transactions/index.blade.php
```

- [ ] **Step 2: Add page header**

Replace lines ~6-9:

```blade
<div class="page-header">
    <h1 class="page-header__title">Transaction History</h1>
    <p class="page-header__subtitle">View all currency exchange transactions</p>
    <div class="page-header__actions">
        <a href="{{ route('transactions.create') }}" class="btn btn--success">+ New Transaction</a>
    </div>
</div>
```

- [ ] **Step 3: Update stats cards with gradient backgrounds**

Replace lines ~12-29 with styled cards:

```blade
<div class="stats-grid">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $transactions->total() }}</div>
        <div class="stat-card__label">Total Transactions</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $transactions->where('type', 'Buy')->count() }}</div>
        <div class="stat-card__label">Buy Transactions</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $transactions->where('type', 'Sell')->count() }}</div>
        <div class="stat-card__label">Sell Transactions</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value">{{ $transactions->where('status', 'Pending')->count() }}</div>
        <div class="stat-card__label">Pending Approval</div>
    </div>
</div>
```

- [ ] **Step 4: Update table class**

Replace `<table role="table">` with `<table class="data-table">` and update `<thead>` and `<tbody>` structure.

- [ ] **Step 5: Commit**

```bash
git add resources/views/transactions/index.blade.php
git commit -m "feat(transactions): apply Corporate Professional theme"
```

---

## Task 5: Customers Index

**Files:**
- Modify: `resources/views/customers/index.blade.php`

- [ ] **Step 1: Read file**

```bash
cat resources/views/customers/index.blade.php
```

- [ ] **Step 2: Apply page-header component**

Replace lines ~6-12:

```blade
<div class="page-header">
    <h1 class="page-header__title">Customer Management</h1>
    <p class="page-header__subtitle">Manage customer profiles, KYC documents, and risk assessments</p>
    <div class="page-header__actions">
        <a href="{{ route('customers.create') }}" class="btn btn--success">+ Add New Customer</a>
    </div>
</div>
```

- [ ] **Step 3: Update filter form styling**

Use `.card` for the filter form container, update inputs to use `.form-input` and `.form-select`.

- [ ] **Step 4: Update table**

Use `.data-table` class, update status badges to use `.status-badge` classes.

- [ ] **Step 5: Update action buttons**

Use `.btn` classes with appropriate variants.

- [ ] **Step 6: Commit**

```bash
git add resources/views/customers/index.blade.php
git commit -m "feat(customers): apply Corporate Professional theme"
```

---

## Task 6: Auth Login Page

**Files:**
- Modify: `resources/views/auth/login.blade.php`

- [ ] **Step 1: Read file**

```bash
cat resources/views/auth/login.blade.php
```

- [ ] **Step 2: Restyle login page with Corporate Professional aesthetic**

- Navy gradient background
- Centered white card
- Gold accent on logo
- Updated button styling

- [ ] **Step 3: Commit**

```bash
git add resources/views/auth/login.blade.php
git commit -m "feat(auth): apply Corporate Professional login theme"
```

---

## Task 7: Compliance Views (Batch)

**Files:**
- Modify: All files in `resources/views/compliance/`

- [ ] **Step 1: Batch update all compliance views**

For each compliance view file:
1. Apply page-header component
2. Use card component for containers
3. Use data-table for tables
4. Use btn classes for actions
5. Use status-badge classes for status indicators

- [ ] **Step 2: Commit**

```bash
git add resources/views/compliance/
git commit -m "feat(compliance): apply Corporate Professional theme to all compliance views"
```

---

## Task 8: Accounting Views (Batch)

**Files:**
- Modify: All files in `resources/views/accounting/`

- [ ] **Step 1: Batch update all accounting views**

Apply same patterns as Task 7.

- [ ] **Step 2: Commit**

```bash
git add resources/views/accounting/
git commit -m "feat(accounting): apply Corporate Professional theme to all accounting views"
```

---

## Task 9: Error Pages

**Files:**
- Modify: `resources/views/errors/403.blade.php`, `404.blade.php`, `500.blade.php`

- [ ] **Step 1: Apply consistent error page styling**

- Navy sidebar visible (if user is logged in)
- Centered error card with gold icon
- Consistent typography

- [ ] **Step 2: Commit**

```bash
git add resources/views/errors/
git commit -m "feat(errors): apply Corporate Professional theme to error pages"
```

---

## Task 10: Remaining Views (Batch)

**Files:**
- Modify: All remaining `.blade.php` files in `resources/views/`

- [ ] **Step 1: Apply theme to remaining views**

Run through all views not yet updated:
- counters/*
- branches/*
- stock-cash/*
- stock-transfers/*
- reports/*
- users/*
- tasks/*
- audit/*
- str/*
- mfa/*
- data-breach-alerts/*

- [ ] **Step 2: Commit**

```bash
git add resources/views/
git commit -m "feat(views): apply Corporate Professional theme to remaining views"
```

---

## Self-Review Checklist

- [ ] All CSS custom properties defined (--color-primary, --color-gold, --font-heading, etc.)
- [ ] All component classes implemented (card, btn, data-table, status-badge, form-input)
- [ ] Sidebar styling matches spec (240px, navy gradient, gold active state)
- [ ] Typography using Merriweather + Source Sans via Google Fonts
- [ ] All view files updated with consistent page-header structure
- [ ] Button variants consistent across all views
- [ ] Status badge classes applied correctly
- [ ] Forms use form-input and form-select classes
- [ ] No placeholder/TODO comments in implementation
- [ ] Each task commits independently with clear message

---

## Execution Options

**Plan complete and saved to `docs/superpowers/plans/2026-04-10-cems-my-frontend-theme-implementation.md`. Two execution options:**

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

Which approach?
