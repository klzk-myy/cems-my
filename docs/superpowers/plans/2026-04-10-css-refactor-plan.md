# CSS Unification Plan - 2026-04-10

## Current State
- `layouts/app.blade.php` loads `app.css` via Vite
- Button classes are consistent (Bootstrap pattern: `btn btn-primary`)
- 4 dynamic inline styles remain (runtime PHP only)
- ~55+ files have `<style>` blocks that duplicate app.css
- Many files have non-dynamic inline styles

## Tasks

### Phase 1: Remove Redundant `<style>` Blocks
Views with `<style>` blocks that duplicate app.css (most are legacy patterns):

- [x] transactions/show.blade.php (~163 lines) ✓
- [x] stock-cash/reconciliation.blade.php (~114 lines) ✓
- [x] auth/login.blade.php (~93 lines) ✓
- [x] dashboard.blade.php ✓
- [x] accounting.blade.php ✓
- [x] branches/*.blade.php (4 files) ✓
- [x] users/*.blade.php (4 files) ✓
- [x] tasks/index.blade.php ✓
- [x] tasks/create.blade.php ✓
- [ ] tasks/show.blade.php
- [ ] tasks/my-tasks.blade.php
- [ ] tasks/overdue.blade.php
- [ ] compliance/rules/*.blade.php
- [ ] (remaining ~40 files with <style> blocks)

**Approach**: Delete `<style>` block, use classes from app.css

### Phase 2: Replace Non-Dynamic Inline Styles
Files with static inline styles that should be CSS classes:

- [ ] branches/show.blade.php (margin-bottom, margin-top, background colors)
- [ ] branches/edit.blade.php
- [ ] mfa/verify.blade.php
- [ ] transactions/show.blade.php
- [ ] stock-cash/reconciliation.blade.php
- [ ] (other files with hardcoded values)

**Approach**: Add missing classes to app.css, replace inline styles

### Phase 3: Consolidate Colors
Hardcoded colors should use existing app.css variables:
- `#2d3748` → `var(--color-gray-700)`
- `#718096` → `var(--color-gray-500)`
- `#3182ce` → `var(--color-primary-lighter)`
- `#38a169` → `var(--color-success)`
- `#e53e3e` → `var(--color-danger)`
- `#dd6b20` → `var(--color-warning)`
- `#1a365d` → `var(--color-primary)`

## Files to Check First (High Impact)

1. **transactions/show.blade.php** - 163 line `<style>` block + many inline styles
2. **stock-cash/reconciliation.blade.php** - 114 line `<style>` block
3. **auth/login.blade.php** - 93 line `<style>` block, isolated page
4. **branches/show.blade.php** - multiple static inline styles
5. **mfa/verify.blade.php** - inline icon color

## Progress
- [x] layouts/app.blade.php unified to load app.css
- [x] app.css has button aliases for Bootstrap pattern compatibility
- [x] 4 dynamic inline styles identified (cannot convert)
- [ ] ~55 files with `<style>` blocks (pending)
- [ ] Multiple files with non-dynamic inline styles (pending)
