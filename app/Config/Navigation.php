<?php

namespace App\Config;

use App\Enums\UserRole;

/**
 * Navigation configuration for CEMS-MY
 *
 * Organized by function and features for BNM compliance workflow
 */
class Navigation
{
    /**
     * Get the complete navigation structure
     * Groups: Operations, Compliance & AML, Accounting & Finance, Reports, System
     */
    public static function get(): array
    {
        return [
            // ============================================================
            // OPERATIONS - Daily operational tasks
            // ============================================================
            'operations' => [
                'label' => 'Operations',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'route' => 'dashboard',
                        'icon' => 'dashboard',
                        'uri' => '/dashboard',
                    ],
                    [
                        'label' => 'Transactions',
                        'route' => 'transactions.index',
                        'icon' => 'exchange',
                        'uri' => '/transactions',
                    ],
                    [
                        'label' => 'Customers',
                        'route' => 'customers.index',
                        'icon' => 'users',
                        'uri' => '/customers',
                    ],
                    [
                        'label' => 'Counters',
                        'route' => 'counters.index',
                        'icon' => 'counter',
                        'uri' => '/counters',
                    ],
                    [
                        'label' => 'Stock & Cash',
                        'route' => 'stock-cash.index',
                        'icon' => 'cash',
                        'uri' => '/stock-cash',
                    ],
                ],
            ],

            // ============================================================
            // COMPLIANCE & AML - BNM regulatory compliance
            // ============================================================
            'compliance' => [
                'label' => 'Compliance & AML',
                'items' => [
                    [
                        'label' => 'Compliance',
                        'route' => 'compliance',
                        'icon' => 'shield',
                        'uri' => '/compliance',
                    ],
                    [
                        'label' => 'Compliance Workspace',
                        'route' => 'compliance.workspace',
                        'icon' => 'workspace',
                        'uri' => '/compliance/workspace',
                        'parent' => 'compliance',
                    ],
                    [
                        'label' => 'Alert Triage',
                        'route' => 'compliance.alerts.index',
                        'icon' => 'alert',
                        'uri' => '/compliance/alerts',
                        'parent' => 'compliance',
                    ],
                    [
                        'label' => 'Cases',
                        'route' => 'compliance.cases.index',
                        'icon' => 'folder',
                        'uri' => '/compliance/cases',
                        'parent' => 'compliance',
                    ],
                    [
                        'label' => 'Flagged Transactions',
                        'route' => 'compliance.flagged',
                        'icon' => 'flag',
                        'uri' => '/compliance/flagged',
                        'parent' => 'compliance',
                    ],
                    [
                        'label' => 'EDD Records',
                        'route' => 'compliance.edd.index',
                        'icon' => 'document',
                        'uri' => '/compliance/edd',
                        'parent' => 'compliance',
                    ],
                    [
                        'label' => 'EDD Templates',
                        'route' => 'compliance.edd-templates.index',
                        'icon' => 'template',
                        'uri' => '/compliance/edd-templates',
                        'parent' => 'compliance',
                    ],
                    [
                        'label' => 'AML Rules',
                        'route' => 'compliance.rules.index',
                        'icon' => 'rule',
                        'uri' => '/compliance/rules',
                        'parent' => 'compliance',
                    ],
                    [
                        'label' => 'Risk Dashboard',
                        'route' => 'compliance.risk-dashboard.index',
                        'icon' => 'trending',
                        'uri' => '/compliance/risk-dashboard',
                        'parent' => 'compliance',
                    ],
                    [
                        'label' => 'STR Studio',
                        'route' => 'compliance.str-studio.index',
                        'icon' => 'document-text',
                        'uri' => '/compliance/str-studio',
                        'parent' => 'compliance',
                    ],
                    [
                        'label' => 'Compliance Reporting',
                        'route' => 'compliance.reporting.index',
                        'icon' => 'chart',
                        'uri' => '/compliance/reporting',
                        'parent' => 'compliance',
                    ],
                    [
                        'label' => 'STR Reports',
                        'route' => 'str.index',
                        'icon' => 'exclamation',
                        'uri' => '/str',
                    ],
                ],
            ],

            // ============================================================
            // ACCOUNTING & FINANCE - Double-entry accounting
            // ============================================================
            'accounting' => [
                'label' => 'Accounting',
                'items' => [
                    [
                        'label' => 'Accounting',
                        'route' => 'accounting',
                        'icon' => 'calculator',
                        'uri' => '/accounting',
                    ],
                    [
                        'label' => 'Journal Entries',
                        'route' => 'accounting.journal',
                        'icon' => 'journal',
                        'uri' => '/accounting/journal',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'New Entry',
                        'route' => 'accounting.journal.create',
                        'icon' => 'plus',
                        'uri' => '/accounting/journal/create',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Workflow',
                        'route' => 'accounting.journal.workflow',
                        'icon' => 'workflow',
                        'uri' => '/accounting/journal/workflow',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Ledger',
                        'route' => 'accounting.ledger',
                        'icon' => 'ledger',
                        'uri' => '/accounting/ledger',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Trial Balance',
                        'route' => 'accounting.trial-balance',
                        'icon' => 'balance',
                        'uri' => '/accounting/trial-balance',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Profit & Loss',
                        'route' => 'accounting.profit-loss',
                        'icon' => 'chart',
                        'uri' => '/accounting/profit-loss',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Balance Sheet',
                        'route' => 'accounting.balance-sheet',
                        'icon' => 'statement',
                        'uri' => '/accounting/balance-sheet',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Cash Flow',
                        'route' => 'accounting.cash-flow',
                        'icon' => 'cashflow',
                        'uri' => '/accounting/cash-flow',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Financial Ratios',
                        'route' => 'accounting.ratios',
                        'icon' => 'ratio',
                        'uri' => '/accounting/ratios',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Periods',
                        'route' => 'accounting.periods',
                        'icon' => 'calendar',
                        'uri' => '/accounting/periods',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Fiscal Years',
                        'route' => 'accounting.fiscal-years',
                        'icon' => 'calendar-alt',
                        'uri' => '/accounting/fiscal-years',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Revaluation',
                        'route' => 'accounting.revaluation',
                        'icon' => 'currency',
                        'uri' => '/accounting/revaluation',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Reconciliation',
                        'route' => 'accounting.reconciliation',
                        'icon' => 'bank',
                        'uri' => '/accounting/reconciliation',
                        'parent' => 'accounting',
                    ],
                    [
                        'label' => 'Budget',
                        'route' => 'accounting.budget',
                        'icon' => 'budget',
                        'uri' => '/accounting/budget',
                        'parent' => 'accounting',
                    ],
                ],
            ],

            // ============================================================
            // REPORTS - BNM compliance reporting
            // ============================================================
            'reports' => [
                'label' => 'Reports',
                'items' => [
                    [
                        'label' => 'Reports',
                        'route' => 'reports',
                        'icon' => 'report',
                        'uri' => '/reports',
                    ],
                    [
                        'label' => 'MSB2 Report',
                        'route' => 'reports.msb2',
                        'icon' => 'daily',
                        'uri' => '/reports/msb2',
                        'parent' => 'reports',
                    ],
                    [
                        'label' => 'LCTR',
                        'route' => 'reports.lctr',
                        'icon' => 'large-cash',
                        'uri' => '/reports/lctr',
                        'parent' => 'reports',
                    ],
                    [
                        'label' => 'LMCA',
                        'route' => 'reports.lmca',
                        'icon' => 'monthly',
                        'uri' => '/reports/lmca',
                        'parent' => 'reports',
                    ],
                    [
                        'label' => 'Quarterly LVR',
                        'route' => 'reports.quarterly-lvr',
                        'icon' => 'quarterly',
                        'uri' => '/reports/quarterly-lvr',
                        'parent' => 'reports',
                    ],
                    [
                        'label' => 'Position Limits',
                        'route' => 'reports.position-limit',
                        'icon' => 'limit',
                        'uri' => '/reports/position-limit',
                        'parent' => 'reports',
                    ],
                    [
                        'label' => 'Report History',
                        'route' => 'reports.history',
                        'icon' => 'history',
                        'uri' => '/reports/history',
                        'parent' => 'reports',
                    ],
                ],
            ],

            // ============================================================
            // SYSTEM - Administrative tasks
            // ============================================================
            'system' => [
                'label' => 'System',
                'items' => [
                    [
                        'label' => 'Tasks',
                        'route' => 'tasks.index',
                        'icon' => 'task',
                        'uri' => '/tasks',
                    ],
                    [
                        'label' => 'Audit Log',
                        'route' => 'audit.index',
                        'icon' => 'audit',
                        'uri' => '/audit',
                    ],
                    [
                        'label' => 'Users',
                        'route' => 'users.index',
                        'icon' => 'user',
                        'uri' => '/users',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get navigation items with role-based filtering
     */
    public static function getForRole(?UserRole $role): array
    {
        $navigation = self::get();

        if (!$role) {
            // Return only operations for unauthenticated
            return [
                'operations' => $navigation['operations'],
            ];
        }

        // Role-based filtering can be added here if needed
        // For now, all items are shown but may be filtered by route middleware
        return $navigation;
    }

    /**
     * Get top-level groups only (for compact navigation)
     */
    public static function getTopLevel(): array
    {
        $navigation = self::get();
        $topLevel = [];

        foreach ($navigation as $key => $group) {
            $topLevelItems = array_filter($group['items'], fn($item) => !isset($item['parent']));
            $topLevel[$key] = [
                'label' => $group['label'],
                'items' => array_values($topLevelItems),
            ];
        }

        return $topLevel;
    }
}
