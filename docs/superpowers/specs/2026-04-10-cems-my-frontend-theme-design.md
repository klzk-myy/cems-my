# CEMS-MY Frontend Theme Redesign — Design Spec

**Date:** 2026-04-10
**Status:** Approved
**Chosen Direction:** Corporate Professional

---

## 1. Concept & Vision

A **Corporate Professional** aesthetic for CEMS-MY — Bank Negara Malaysia's Currency Exchange Management System. The interface should feel authoritative, trustworthy, and distinctly financial-institutional. This is a BNM-compliant MSB system where precision and credibility are as important as usability. The visual language should evoke established banks and money services businesses, not consumer fintech.

---

## 2. Design Language

### Aesthetic Direction
Classic financial institution — deep navy authority with gold accents, refined typography, and structured layouts. Clean without being sterile. Professional without being cold.

### Color Palette

| Role | Color | Hex |
|------|-------|-----|
| Primary (sidebar, headers) | Deep Navy | `#1a365d` |
| Primary Light | Navy Mid | `#2c5282` |
| Primary Lighter | Navy Bright | `#3182ce` |
| Gold Accent | Classic Gold | `#D4AF37` |
| Background | Off-White | `#f7fafc` |
| Card Background | White | `#ffffff` |
| Text Primary | Charcoal | `#1a202c` |
| Text Secondary | Slate | `#4a5568` |
| Text Muted | Gray | `#718096` |
| Success | Forest Green | `#38a169` |
| Warning | Burnt Orange | `#dd6b20` |
| Danger | Crimson | `#e53e3e` |
| Border | Light Gray | `#e2e8f0` |

### Typography

**Headings:** Merriweather (serif) — elegant, academic, excellent for financial reporting
**Body:** Source Sans (sans-serif) — clean, highly readable, professional
**Monospace (numbers/data):** JetBrains Mono — clarity for financial figures

```
--font-heading: 'Merriweather', Georgia, serif;
--font-body: 'Source Sans 3', -apple-system, sans-serif;
--font-mono: 'JetBrains Mono', 'Fira Code', monospace;
```

### Spatial System

- Base unit: 4px
- Border radius: 4px (small), 6px (medium), 8px (large), 12px (cards)
- Shadows: Subtle, layered — `0 1px 3px rgba(0,0,0,0.12)` (card), `0 4px 12px rgba(0,0,0,0.15)` (elevated)
- Sidebar width: 240px (compact icon + label)
- Content max-width: fluid, max 1400px
- Grid: 12-column responsive grid

### Motion Philosophy

Minimal, purposeful motion only:
- Transitions: 150-200ms ease for hover states
- No decorative animations
- Focus indicators for accessibility
- Sidebar collapse: 200ms ease-out

### Visual Assets

- **Icons:** Heroicons (outline) — consistent stroke weight, professional feel
- **Decorative:** Minimal — no textures, gradients only for status indicators
- **Data visualization:** Clean charts with navy/gold palette, no 3D effects

---

## 3. Layout & Structure

### Navigation: Compact Icon + Label

240px fixed sidebar with:
- **Header:** Logo + system name (CEMS-MY) with gold accent
- **Navigation groups:** Collapsible sections organized by function
  - Operations (Dashboard, Transactions, Customers)
  - Counter Management (Counters, Branches)
  - Stock Management (Stock & Cash, Stock Transfers)
  - Compliance & AML (Compliance, STR Reports)
  - Accounting (Accounting, Journal, Ledger, Trial Balance, P&L, Balance Sheet, etc.)
  - Reports (MSB2, LCTR, LMCA, Quarterly LVR, etc.)
  - System (Tasks, Audit Log, Users, Data Breach Alerts)
- **Logout:** Fixed at bottom of sidebar
- **Active state:** Gold left border + subtle navy highlight

### Page Structure

```
┌─────────────────────────────────────────────────────────┐
│ 240px Sidebar │  Main Content Area                      │
│               │  ┌─────────────────────────────────────┐ │
│ [Logo]        │  │ Page Header (title + actions)       │ │
│               │  ├─────────────────────────────────────┤ │
│ Operations    │  │ Content (cards, tables, forms)      │ │
│   Dashboard   │  │                                     │ │
│   Transact... │  │                                     │ │
│               │  └─────────────────────────────────────┘ │
│ Compliance    │  ┌─────────────────────────────────────┐ │
│   Compliance │  │ Footer                               │ │
│   STR...     │  └─────────────────────────────────────┘ │
│               │                                          │
│ [Logout]      │                                          │
└─────────────────────────────────────────────────────────┘
```

### Responsive Strategy

- **Desktop (≥1280px):** Full 240px sidebar + content
- **Tablet (768-1279px):** Collapsed sidebar (64px icons only)
- **Mobile (<768px):** Hamburger menu, full-width content

---

## 4. Features & Interactions

### Core Components

#### Cards
- White background, 12px border-radius, subtle shadow
- Gold top border (2px) for featured/primary cards
- Hover: slight shadow elevation

#### Buttons
- **Primary:** Navy background, white text
- **Success:** Green background (#38a169)
- **Danger:** Crimson background (#e53e3e)
- **Ghost:** Transparent with navy text
- All buttons: 6px radius, 150ms hover transition

#### Tables
- Zebra striping: white / #f7fafc
- Header: uppercase, smaller font, gray-500 text
- Row hover: light blue tint (#ebf8ff)
- Sortable columns: chevron indicator

#### Forms
- Labels: uppercase, 12px, semibold
- Inputs: 2px border, 6px radius, focus = navy border
- Validation: inline red text below field
- Select dropdowns: custom styled to match

#### Status Badges
- Pill-shaped, color-coded by status
- Completed: green bg / dark green text
- Pending: amber bg / dark amber text
- Flagged: red bg / dark red text

### Interaction Details

| Element | Hover | Active | Focus |
|---------|-------|--------|-------|
| Nav link | Navy/20 bg | Gold left border | Blue outline |
| Button | Darken 10% | Darken 15% | Blue ring | 
| Table row | Light tint | — | Blue ring |
| Card | Lift shadow | — | — |

---

## 5. Component Inventory

### Sidebar (`layouts/app.blade.php`)
- Logo area with gold accent on system name
- Navigation groups with collapse/expand
- Active indicator: 3px gold left border
- Fixed position, full viewport height
- Scrollable if content exceeds viewport

### Page Header
- Title (h1, Merriweather, 24px)
- Subtitle/description (Source Sans, 14px, muted)
- Action buttons (top-right aligned)

### Data Tables
- Full-width with horizontal scroll on overflow
- Pagination below table
- Empty state with contextual message

### Forms
- Grouped in cards with clear section labels
- Required field indicator (gold asterisk)
- Inline help text (muted, smaller)

### Status Widgets
- Dashboard stat cards: gradient backgrounds
- System status: compact inline display
- Alert summaries: color-coded pills

### Modals/Dialogs
- Centered, max-width 480px
- Backdrop blur (8px)
- Slide-in animation (200ms)

---

## 6. Technical Approach

### Framework
- **Laravel 10.x** with Blade templates
- **Tailwind CSS v4** via `@import "tailwindcss"` (CSS-based config)
- **Custom CSS layer** in `resources/css/app.css` for component classes

### Implementation Scope

**Phase 1: Layout & Theme Foundation**
1. Update `app.css` — Corporate Professional color variables, typography
2. Update `layouts/app.blade.php` — Navigation structure, sidebar styling
3. Update `resources/css/app.css` — Button, card, table, form component classes

**Phase 2: Dashboard & Key Pages**
4. `dashboard.blade.php` — Stats cards, quick actions
5. `transactions/index.blade.php` — Table with filtering
6. `customers/index.blade.php` — Customer table with risk badges

**Phase 3: Compliance & Accounting**
7. `compliance/*` views — Alert triage, cases, EDD
8. `accounting/*` views — Journal, ledger, financial statements

**Phase 4: Refinement**
9. Error pages (403, 404, 500)
10. Login page
11. Responsive refinements

### Key Files to Modify

| File | Changes |
|------|---------|
| `resources/css/app.css` | Theme variables, component classes |
| `resources/views/layouts/app.blade.php` | Sidebar, navigation, layout |
| `resources/views/dashboard.blade.php` | Stats, quick actions |
| `resources/views/transactions/index.blade.php` | Transaction table |
| `resources/views/customers/index.blade.php` | Customer table |
| `resources/views/auth/login.blade.php` | Login form |
| `resources/views/errors/*.blade.php` | Error pages |

### CSS Architecture

```css
/* Theme Variables */
:root {
  --color-navy: #1a365d;
  --color-navy-light: #2c5282;
  --color-navy-lighter: #3182ce;
  --color-gold: #D4AF37;
  /* ... */
}

/* Typography */
@import url('https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Source+Sans+3:wght@400;600;700&family=JetBrains+Mono:wght@400;500');

/* Component Classes */
.card { ... }
.btn { ... }
.data-table { ... }
/* ... */
```

---

## 7. Approved Decisions

| Decision | Selection |
|----------|-----------|
| Design Direction | Corporate Professional |
| Navigation | Compact Icon + Label (240px sidebar) |
| Typography | Merriweather (headings) + Source Sans (body) |
| Accent Colors | Royal Blue Links (#2563EB) |

---

## 8. Next Steps

1. Invoke `superpowers:writing-plans` skill to create implementation plan
2. Execute implementation in phases
3. Test across all view files
4. Verify responsive behavior
