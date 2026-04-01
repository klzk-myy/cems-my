# Missing Views Design Specification

**Date:** 2026-04-02
**Project:** CEMS-MY (Currency Exchange Management System - Malaysia)
**Scope:** Design specification for 4 missing Blade view templates
**Status:** Approved

---

## Executive Summary

This document specifies the design for 4 missing view templates in the CEMS-MY Laravel application. The views will follow the existing design system (dark blue sidebar, card-based layouts, consistent styling) and implement comprehensive functionality for compliance monitoring and regulatory reporting.

### Missing Views
1. `compliance.blade.php` - Compliance Portal for AML monitoring
2. `reports.blade.php` - Reports Dashboard hub
3. `reports/lctr.blade.php` - LCTR (Large Currency Transaction Report)
4. `reports/msb2.blade.php` - MSB(2) Daily Transaction Summary Report

---

## Design System Foundation

### Visual Identity
- **Sidebar:** Dark blue (#1a365d) with white text, fixed width 220px
- **Content Area:** Light gray background (#f5f5f5)
- **Cards:** White background, subtle border, 8px border-radius
- **Typography:** System fonts (-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto)
- **Status Badges:** Color-coded with rounded corners
- **Tables:** Clean with hover effects, striped rows optional

### Common CSS Classes
```css
/* Header Pattern */
.page-header { margin-bottom: 1.5rem; }
.page-header h2 { color: #2d3748; margin-bottom: 0.5rem; }
.page-header p { color: #718096; }

/* Cards */
.card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
.card h2 { margin-bottom: 1rem; color: #2d3748; }

/* Summary Boxes */
.summary-box { background: #f7fafc; border-radius: 8px; padding: 1.5rem; text-align: center; }
.summary-value { font-size: 2rem; font-weight: 700; color: #1a365d; }
.summary-label { color: #718096; margin-top: 0.5rem; font-size: 0.875rem; }

/* Status Badges */
.status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
.status-open { background: #fed7d7; color: #c53030; }
.status-review { background: #feebc8; color: #c05621; }
.status-resolved { background: #c6f6d5; color: #276749; }
.status-pending { background: #e2e8f0; color: #4a5568; }

/* Tables */
table { width: 100%; border-collapse: collapse; }
th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
th { font-weight: 600; color: #4a5568; background: #f7fafc; }
tr:hover { background: #f7fafc; }

/* Buttons */
.btn { display: inline-block; padding: 0.625rem 1.25rem; border-radius: 4px; font-weight: 500; text-decoration: none; border: none; cursor: pointer; }
.btn-primary { background: #3182ce; color: white; }
.btn-success { background: #38a169; color: white; }
.btn-warning { background: #d69e2e; color: white; }
.btn-danger { background: #e53e3e; color: white; }
.btn-secondary { background: #e2e8f0; color: #4a5568; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.875rem; }

/* Grid */
.grid { display: grid; gap: 1rem; }
.flex { display: flex; }
.justify-between { justify-content: space-between; }
.items-center { align-items: center; }
.gap-2 { gap: 0.5rem; }
.gap-4 { gap: 1rem; }

/* Form Elements */
.form-group { margin-bottom: 1rem; }
.form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #4a5568; }
.form-input { width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px; }
.form-select { width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px; }

/* Alerts */
.alert { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
.alert-info { background: #ebf8ff; border: 1px solid #bee3f8; color: #2c5282; }
.alert-warning { background: #fffff0; border: 1px solid #faf089; color: #744210; }
.alert-danger { background: #fed7d7; border: 1px solid #fc8181; color: #c53030; }
```

---

## View 1: Compliance Portal (`compliance.blade.php`)

### Purpose
Allow compliance officers to review, assign, and resolve flagged transactions for Anti-Money Laundering (AML) monitoring.

### Route
```php
Route::get('/compliance', [DashboardController::class, 'compliance'])
    ->name('compliance')
    ->middleware('role:compliance');
```

### Data Requirements
- `$flags` - Paginated collection of FlaggedTransaction with relations (transaction, customer, assignedTo, reviewer)
- `$stats` - Array with counts: open, under_review, resolved_today, high_priority
- `$filter_status` - Current filter value (optional)
- `$filter_type` - Current filter value (optional)

### Layout Structure
```
@extends('layouts.app')
├─ @section('title', 'Compliance Portal - CEMS-MY')
├─ @section('styles')
│  └─ Compliance-specific CSS
└─ @section('content')
   ├─ Page Header
   │  ├─ Title: "Compliance Portal - AML Monitoring"
   │  └─ Subtitle: "Review and resolve suspicious transaction flags"
   ├─ Summary Cards (4-column grid)
   │  ├─ Open Flags (red badge)
   │  ├─ Under Review (yellow badge)
   │  ├─ Resolved Today (green badge)
   │  └─ High Priority (alert badge)
   ├─ Filter Bar
   │  ├─ Status dropdown (All, Open, Under Review, Resolved)
   │  ├─ Flag Type dropdown (All, Velocity, Structuring, EDD_Required, Sanction_Match, Manual)
   │  ├─ Date Range (From/To)
   │  └─ Apply/Reset buttons
   ├─ Main Table Card
   │  ├─ Table with columns:
   │  │  ├─ Flag ID
   │  │  ├─ Transaction ID (link)
   │  │  ├─ Customer (masked name + ID)
   │  │  ├─ Flag Type (badge)
   │  │  ├─ Flag Reason
   │  │  ├─ Status (badge)
   │  │  ├─ Assigned To
   │  │  ├─ Created At
   │  │  └─ Actions (dropdown or buttons)
   │  └─ Pagination
   └─ Bulk Actions Bar (optional)
      └─ Assign Selected, Export, Mark Resolved
```

### Flag Type Badge Colors
- **Velocity:** Blue (#ebf8ff / #2b6cb0)
- **Structuring:** Orange (#feebc8 / #c05621)
- **EDD_Required:** Purple (#e9d8fd / #6b46c1)
- **Sanction_Match:** Red (#fed7d7 / #c53030)
- **Manual:** Gray (#e2e8f0 / #4a5568)
- **PEP_Status:** Yellow (#fffff0 / #744210)

### Action Buttons per Row
1. **View** - Link to transaction details
2. **Assign** - Dropdown to assign to compliance officer (POST to assign route)
3. **Resolve** - Modal with notes field (POST to resolve route)

### Empty State
When no flags exist:
- Display alert-info: "No flagged transactions found. Great! Your compliance monitoring is working effectively."
- Show filter reset button

### Controller Updates Needed
Add filtering logic to `DashboardController::compliance()` method:
```php
public function compliance(Request $request)
{
    if (!auth()->user()->isComplianceOfficer()) {
        abort(403);
    }

    $query = FlaggedTransaction::with(['transaction.customer', 'assignedTo', 'reviewer']);
    
    // Apply filters
    if ($request->has('status')) {
        $query->where('status', $request->status);
    }
    if ($request->has('flag_type')) {
        $query->where('flag_type', $request->flag_type);
    }
    
    $flags = $query->orderBy('created_at', 'desc')->paginate(20);
    
    // Calculate stats
    $stats = [
        'open' => FlaggedTransaction::where('status', 'Open')->count(),
        'under_review' => FlaggedTransaction::where('status', 'Under_Review')->count(),
        'resolved_today' => FlaggedTransaction::where('status', 'Resolved')
            ->whereDate('resolved_at', today())->count(),
        'high_priority' => FlaggedTransaction::whereIn('flag_type', ['Sanction_Match', 'Structuring'])
            ->where('status', '!=', 'Resolved')->count(),
    ];
    
    return view('compliance', compact('flags', 'stats'));
}
```

---

## View 2: Reports Dashboard (`reports.blade.php`)

### Purpose
Central hub for generating and accessing all regulatory and financial reports.

### Route
```php
Route::get('/reports', [DashboardController::class, 'reports'])
    ->name('reports')
    ->middleware('role:manager');
```

### Data Requirements
- `$recentReports` - Collection of last 10 generated reports
- `$reportCards` - Array of available report types with metadata

### Layout Structure
```
@extends('layouts.app')
├─ @section('title', 'Reports & Analytics - CEMS-MY')
├─ @section('styles')
│  └─ Reports-specific CSS (card grid styles)
└─ @section('content')
   ├─ Page Header
   │  ├─ Title: "Reports & Analytics"
   │  └─ Subtitle: "Generate regulatory and financial reports"
   ├─ Report Cards Grid (responsive: 1 col mobile, 2 col tablet, 3 col desktop)
   │  ├─ LCTR Card
   │  ├─ MSB(2) Card
   │  ├─ Trial Balance Card
   │  ├─ Profit & Loss Card
   │  ├─ Balance Sheet Card
   │  ├─ Customer Risk Report Card
   │  ├─ Currency Position Card
   │  └─ Audit Trail Card
   └─ Recent Reports Section
      ├─ Section Header with "View All" link
      └─ Table
         ├─ Report Type
         ├─ Period
         ├─ Generated By
         ├─ Generated At
         ├─ Status
         └─ Actions (Download, View)
```

### Report Card Design
Each card contains:
- **Icon/Emoji:** Representative icon (e.g., 📊 for LCTR, 📈 for P&L)
- **Title:** Report name (e.g., "LCTR Report")
- **Description:** Brief explanation (e.g., "Large Currency Transaction Report for BNM compliance")
- **Meta:** Last generated date or "Never generated"
- **Button:** Primary CTA (e.g., "Generate Report")
- **Secondary Link:** "Learn more" or "View history"

### Card Color Coding
- **Regulatory (LCTR, MSB2):** Blue accent border
- **Financial (Trial Balance, P&L, Balance Sheet):** Green accent border
- **Operational (Currency Position, Audit Trail):** Gray accent border
- **Risk (Customer Risk):** Orange/Yellow accent border

### Report Cards Detail

#### 1. LCTR Report
- **Icon:** 🏛️
- **Description:** "Bank Negara Malaysia Large Currency Transaction Report"
- **Frequency:** Monthly (due by 10th of following month)
- **Quick Action:** "Select Month →"
- **Route:** `/reports/lctr`

#### 2. MSB(2) Report
- **Icon:** 📋
- **Description:** "Daily Money Services Business Transaction Summary"
- **Frequency:** Daily (due next business day)
- **Quick Action:** "Select Date →"
- **Route:** `/reports/msb2`

#### 3. Trial Balance
- **Icon:** ⚖️
- **Description:** "Chart of accounts with debit/credit balances"
- **Frequency:** On-demand
- **Quick Action:** "Generate →"
- **Route:** `/accounting/trial-balance`

#### 4. Profit & Loss
- **Icon:** 📈
- **Description:** "Revenue and expense statement"
- **Frequency:** Monthly/Quarterly/Annual
- **Quick Action:** "Select Period →"
- **Route:** `/accounting/profit-loss`

#### 5. Balance Sheet
- **Icon:** 📊
- **Description:** "Assets, liabilities, and equity snapshot"
- **Frequency:** Monthly/Quarterly/Annual
- **Quick Action:** "Select Date →"
- **Route:** `/accounting/balance-sheet`

#### 6. Customer Risk Report
- **Icon:** ⚠️
- **Description:** "High-risk customer analysis and flags"
- **Frequency:** Weekly
- **Quick Action:** "Generate →"
- **Route:** `/compliance/risk-report` (new route needed)

#### 7. Currency Position Report
- **Icon:** 💱
- **Description:** "Current inventory and unrealized P&L"
- **Frequency:** Real-time/On-demand
- **Quick Action:** "View Current →"
- **Route:** `/accounting`

#### 8. Audit Trail
- **Icon:** 🔍
- **Description:** "Complete system activity log"
- **Frequency:** On-demand
- **Quick Action:** "View Logs →"
- **Route:** `/admin/audit-logs` (new route needed)

### Recent Reports Table Columns
1. **Report Type** - Badge with icon (LCTR, MSB2, etc.)
2. **Period** - Date range (e.g., "2026-03" or "2026-04-01")
3. **Generated By** - User name
4. **Generated At** - Timestamp with relative time (e.g., "2 hours ago")
5. **Status** - Badge (Generated, Downloaded, Submitted)
6. **Actions** - Download button, View button

### Empty State for Recent Reports
- Display alert-info: "No reports generated yet. Select a report type above to get started."

---

## View 3: LCTR Report (`reports/lctr.blade.php`)

### Purpose
Generate and review Large Currency Transaction Reports for Bank Negara Malaysia compliance (transactions ≥ RM 25,000).

### Route
```php
Route::get('/reports/lctr', [ReportController::class, 'lctr'])
    ->name('reports.lctr')
    ->middleware('role:manager');
```

### Data Requirements
- `$month` - Selected month (format: Y-m)
- `$report` - Generated report data array (optional, if already generated)
- `$transactions` - Collection of qualifying transactions with relations
- `$stats` - Summary statistics

### Layout Structure
```
@extends('layouts.app')
├─ @section('title', 'LCTR Report - CEMS-MY')
├─ @section('styles')
│  └─ LCTR-specific CSS
└─ @section('content')
   ├─ Breadcrumb (optional)
   │  └─ Reports → LCTR
   ├─ Page Header
   │  ├─ Title: "LCTR Report"
   │  └─ Subtitle: "Large Currency Transaction Report"
   ├─ Control Card
   │  ├─ Month Selector (input type="month")
   │  ├─ Report Status Badge (Not Generated / Generated / Submitted)
   │  └─ Action Buttons
   │     ├─ Generate Report (primary)
   │     ├─ Download CSV (secondary, disabled if not generated)
   │     └─ Mark as Submitted (secondary, disabled if already submitted)
   ├─ Summary Cards (4-column grid)
   │  ├─ Qualifying Transactions (≥ RM 25,000)
   │  ├─ Total Amount (MYR)
   │  ├─ Unique Customers
   │  └─ Report Period
   ├─ Compliance Alert (conditional)
   │  └─ Warning if pending transactions included
   ├─ Preview Table Card
   │  ├─ Section Title: "Transaction Details"
   │  ├─ Info text: "Showing first 50 transactions. Download CSV for complete report."
   │  └─ Table
   │     ├─ Transaction ID
   │     ├─ Date
   │     ├─ Time
   │     ├─ Customer ID
   │     ├─ Customer Name (masked)
   │     ├─ ID Type
   │     ├─ Amount (MYR)
   │     ├─ Amount (Foreign)
   │     ├─ Currency
   │     ├─ Type (Buy/Sell)
   │     ├─ Branch
   │     └─ Teller
   ├─ Pagination (if preview > 50 rows)
   └─ Compliance Footer
      ├─ Threshold Info: "RM 25,000 per transaction"
      ├─ Deadline: "Due by 10th of following month"
      └─ Next Deadline: "2026-05-10"
```

### Report Status Badges
- **Not Generated:** Gray badge
- **Generated:** Blue badge with timestamp
- **Submitted:** Green badge with submission date
- **Overdue:** Red badge (if past 10th of following month)

### Customer Name Masking
For privacy compliance:
- Show first 2 characters + asterisks + last 2 characters
- Example: "John Smith" → "Jo******th"
- Use helper: `maskName($fullName)`

### Transaction Qualification Rules
Include transactions where:
- `amount_local >= 25000`
- `status = 'Completed'` (exclude Pending, OnHold)
- Date within selected month
- Sort by `created_at` ascending (oldest first)

### CSV Export Format
Headers (per BNM specification):
```csv
Transaction_ID,Date,Time,Customer_ID,Customer_Name,ID_Type,Amount_Local,Amount_Foreign,Currency,Transaction_Type,Branch_ID,Teller_ID
```

### Compliance Warnings
Display alert-warning if:
- Any qualifying transactions are still in "Pending" status
- Submission deadline is within 3 days
- Previous month report not yet generated

### Controller Updates
Ensure `ReportController::lctr()` passes:
```php
public function lctr(Request $request)
{
    $this->requireManagerOrAdmin();
    
    $month = $request->input('month', now()->format('Y-m'));
    
    // Check if report already generated
    $reportGenerated = ReportGenerated::where('report_type', 'LCTR')
        ->where('period_start', now()->parse($month)->startOfMonth())
        ->first();
    
    // Get qualifying transactions
    $startDate = now()->parse($month)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();
    
    $transactions = Transaction::where('amount_local', '>=', 25000)
        ->where('status', 'Completed')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->with(['customer', 'user'])
        ->orderBy('created_at', 'asc')
        ->get();
    
    $stats = [
        'count' => $transactions->count(),
        'total_amount' => $transactions->sum('amount_local'),
        'unique_customers' => $transactions->pluck('customer_id')->unique()->count(),
        'period' => $month,
    ];
    
    return view('reports.lctr', compact('month', 'transactions', 'stats', 'reportGenerated'));
}
```

---

## View 4: MSB(2) Report (`reports/msb2.blade.php`)

### Purpose
Generate daily Money Services Business transaction summary reports for BNM compliance.

### Route
```php
Route::get('/reports/msb2', [ReportController::class, 'msb2'])
    ->name('reports.msb2')
    ->middleware('role:manager');
```

### Data Requirements
- `$date` - Selected date (default: yesterday)
- `$report` - Generated report data (optional)
- `$summary` - Currency-wise summary data
- `$stats` - Overall statistics

### Layout Structure
```
@extends('layouts.app')
├─ @section('title', 'MSB(2) Report - CEMS-MY')
├─ @section('styles')
│  └─ MSB2-specific CSS (negative number styling)
└─ @section('content')
   ├─ Breadcrumb (optional)
   │  └─ Reports → MSB(2)
   ├─ Page Header
   │  ├─ Title: "MSB(2) Report"
   │  └─ Subtitle: "Daily Money Services Business Transaction Summary"
   ├─ Control Card
   │  ├─ Date Picker (input type="date")
   │  ├─ Report Status Badge
   │  └─ Action Buttons
   │     ├─ Generate Report
   │     ├─ Download CSV
   │     └─ Mark as Submitted
   ├─ Summary Cards (4-column grid)
   │  ├─ Total Transactions
   │  ├─ Total Buy Volume (MYR)
   │  ├─ Total Sell Volume (MYR)
   │  └─ Net Position (Buy - Sell)
   ├─ Currency Breakdown Card
   │  ├─ Section Title: "Currency Summary"
   │  └─ Table
   │     ├─ Currency Code
   │     ├─ Buy Volume (Foreign)
   │     ├─ Buy Count
   │     ├─ Buy Amount (MYR)
   │     ├─ Sell Volume (Foreign)
   │     ├─ Sell Count
   │     ├─ Sell Amount (MYR)
   │     ├─ Net Volume (Foreign)
   │     └─ Net Amount (MYR)
   │  └─ Grand Total Row (bold, highlighted)
   ├─ Validation Alerts (conditional)
   │  └─ Warning if data inconsistencies detected
   └─ Compliance Footer
      ├─ Reporting Info: "All transactions included"
      ├─ Deadline: "Due next business day"
      └─ Next Business Day: "2026-04-03"
```

### Summary Data Structure
Query should return:
```php
$summary = DB::table('transactions')
    ->select(
        'currency_code',
        DB::raw("SUM(CASE WHEN type = 'Buy' THEN amount_foreign ELSE 0 END) as buy_volume_foreign"),
        DB::raw("SUM(CASE WHEN type = 'Buy' THEN amount_local ELSE 0 END) as buy_amount_myr"),
        DB::raw("COUNT(CASE WHEN type = 'Buy' THEN 1 END) as buy_count"),
        DB::raw("SUM(CASE WHEN type = 'Sell' THEN amount_foreign ELSE 0 END) as sell_volume_foreign"),
        DB::raw("SUM(CASE WHEN type = 'Sell' THEN amount_local ELSE 0 END) as sell_amount_myr"),
        DB::raw("COUNT(CASE WHEN type = 'Sell' THEN 1 END) as sell_count")
    )
    ->whereDate('created_at', $date)
    ->where('status', 'Completed')
    ->groupBy('currency_code')
    ->orderBy('currency_code')
    ->get();
```

### Table Calculations
For each currency row:
- **Net Volume (Foreign):** Buy Volume - Sell Volume
- **Net Amount (MYR):** Buy Amount - Sell Amount

### Number Formatting
- Foreign amounts: 4 decimal places (e.g., "1,234.5678")
- MYR amounts: 2 decimal places (e.g., "12,345.67")
- Negative numbers: Show in red with minus sign (e.g., "-5,432.10")

### Grand Total Row
At bottom of table, show:
- **Currency:** "TOTAL"
- Sum of all buy volumes, counts, amounts
- Sum of all sell volumes, counts, amounts
- Calculated net values

### CSV Export Format
Headers:
```csv
Currency_Code,Buy_Volume_Foreign,Buy_Count,Buy_Amount_MYR,Sell_Volume_Foreign,Sell_Count,Sell_Amount_MYR,Net_Volume_Foreign,Net_Amount_MYR
```

### Validation Checks
Display alert-warning if:
- Any currency has negative net position (more sold than bought)
- Transaction count mismatch (cross-check with raw data)
- Date is today (report should be for completed business day)

### Date Selection Rules
- Default to yesterday (most recent completed business day)
- Disable future dates
- Allow weekends (transactions may occur)

### Controller Updates
```php
public function msb2(Request $request)
{
    $this->requireManagerOrAdmin();
    
    $date = $request->input('date', now()->subDay()->toDateString());
    
    // Check existing report
    $reportGenerated = ReportGenerated::where('report_type', 'MSB2')
        ->whereDate('period_start', $date)
        ->first();
    
    // Get summary data
    $summary = DB::table('transactions')
        ->select(
            'currency_code',
            DB::raw("SUM(CASE WHEN type = 'Buy' THEN amount_foreign ELSE 0 END) as buy_volume_foreign"),
            DB::raw("SUM(CASE WHEN type = 'Buy' THEN amount_local ELSE 0 END) as buy_amount_myr"),
            DB::raw("COUNT(CASE WHEN type = 'Buy' THEN 1 END) as buy_count"),
            DB::raw("SUM(CASE WHEN type = 'Sell' THEN amount_foreign ELSE 0 END) as sell_volume_foreign"),
            DB::raw("SUM(CASE WHEN type = 'Sell' THEN amount_local ELSE 0 END) as sell_amount_myr"),
            DB::raw("COUNT(CASE WHEN type = 'Sell' THEN 1 END) as sell_count")
        )
        ->whereDate('created_at', $date)
        ->where('status', 'Completed')
        ->groupBy('currency_code')
        ->orderBy('currency_code')
        ->get();
    
    // Calculate totals
    $stats = [
        'total_transactions' => $summary->sum('buy_count') + $summary->sum('sell_count'),
        'total_buy_myr' => $summary->sum('buy_amount_myr'),
        'total_sell_myr' => $summary->sum('sell_amount_myr'),
        'net_position' => $summary->sum('buy_amount_myr') - $summary->sum('sell_amount_myr'),
    ];
    
    return view('reports.msb2', compact('date', 'summary', 'stats', 'reportGenerated'));
}
```

---

## Common Features Across All Views

### Pagination
- Use Laravel's built-in pagination: `$items->links()`
- Style with custom CSS to match theme
- Default: 20 items per page

### Empty States
All tables should handle empty data gracefully:
- Display friendly message
- Provide action to create/populate data
- Use alert-info styling

### Loading States
For AJAX-generated content:
- Show spinner or "Generating..." text
- Disable buttons during processing
- Show success/error messages

### Date Formatting
- Display: "Jan 15, 2026" or "15 Jan 2026"
- Input: HTML5 date/month inputs
- Storage: ISO 8601 (Y-m-d, Y-m)

### Number Formatting
- MYR amounts: `number_format($amount, 2)` with RM prefix
- Foreign amounts: `number_format($amount, 4)`
- Large numbers: Include thousand separators

### Responsive Design
- Tables: Horizontal scroll on mobile
- Cards: Stack vertically on narrow screens
- Forms: Full-width inputs on mobile

### Accessibility
- Semantic HTML (table, thead, tbody)
- ARIA labels on interactive elements
- Keyboard navigation support
- Sufficient color contrast

---

## Implementation Checklist

### Files to Create
- [ ] `resources/views/compliance.blade.php`
- [ ] `resources/views/reports.blade.php`
- [ ] `resources/views/reports/lctr.blade.php`
- [ ] `resources/views/reports/msb2.blade.php`

### Controller Updates
- [ ] Update `DashboardController::compliance()` - Add filtering logic
- [ ] Update `DashboardController::reports()` - Add recent reports data
- [ ] Update `ReportController::lctr()` - Add transaction preview data
- [ ] Update `ReportController::msb2()` - Add summary data

### Service Updates
- [ ] Update `ReportingService::generateLCTRData()` - Ensure consistent format
- [ ] Update `ReportingService::generateMSB2Data()` - Ensure consistent format

### Routes (Verify Existing)
- [ ] `GET /compliance` → `DashboardController::compliance`
- [ ] `GET /reports` → `DashboardController::reports`
- [ ] `GET /reports/lctr` → `ReportController::lctr`
- [ ] `GET /reports/msb2` → `ReportController::msb2`

### Testing
- [ ] Test compliance view loads with sample flags
- [ ] Test filters on compliance page
- [ ] Test report generation for LCTR
- [ ] Test report generation for MSB2
- [ ] Test CSV downloads
- [ ] Test responsive layout on mobile
- [ ] Test empty states

---

## Success Criteria

1. ✅ All 4 views render without errors
2. ✅ Views match existing design system (colors, typography, spacing)
3. ✅ Data displays correctly from database
4. ✅ Filters and search work as expected
5. ✅ CSV export generates correct format
6. ✅ Responsive design works on mobile/tablet/desktop
7. ✅ All actions (assign, resolve, generate) function correctly
8. ✅ Empty states display appropriately
9. ✅ Loading states provide user feedback
10. ✅ Accessibility standards met (WCAG 2.1 AA)

---

## Design Approval

**Status:** ✅ Approved by user on 2026-04-02

**Next Step:** Proceed to implementation planning using the `writing-plans` skill.

---

## Appendix: Blade Syntax Reference

### Common Patterns Used
```blade
{{-- Variable output with escaping --}}
{{ $variable }}

{{-- Unescaped output (use carefully) --}}
{!! $htmlContent !!}

{{-- Conditional --}}
@if($condition)
    content
@elseif($otherCondition)
    other content
@else
    default content
@endif

{{-- Loop --}}
@foreach($items as $item)
    {{ $item->name }}
@endforeach

{{-- Empty check --}}
@forelse($items as $item)
    {{ $item->name }}
@empty
    <p>No items found</p>
@endforelse

{{-- Switch/Case --}}
@switch($status)
    @case('open')
        <span class="status-open">Open</span>
        @break
    @case('resolved')
        <span class="status-resolved">Resolved</span>
        @break
    @default
        <span>Unknown</span>
@endswitch

{{-- PHP block --}}
@php
    $computed = $a + $b;
@endphp

{{-- Include sub-view --}}
@include('partials.alert', ['type' => 'info', 'message' => 'Hello'])

{{-- CSRF token in forms --}}
<form method="POST">
    @csrf
    @method('DELETE') {{-- for DELETE requests --}}
</form
```

---

**End of Design Specification**
