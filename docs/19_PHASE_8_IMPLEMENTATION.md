# Phase 8 Implementation: Dashboard, Reports and Operations

## Status

Phase 8 is in progress. The live owner dashboard, first operational reporting slice, and business/branch configuration workspace are complete. Do not advance to Phase 9 until the remaining Phase 8 items listed below are implemented and verified.

## Delivered dashboard

- Tenant- and branch-scoped live KPI cards for sales, gross profit, expenses, customer debt, inventory value, payment-account balance and pending approvals.
- Seven-day sales trend, low-stock alerts and recent sales.
- A workspace chooser when no business and branch have been selected.
- Permission-aware links into operational areas.

## Delivered reports

- A tenant- and branch-scoped reports workspace with custom date filtering.
- Fourteen live reports: sales, gross profit, purchases, inventory valuation, stock movements, low stock, fast/slow movers, posted expenses, payments, customer debt, salesperson performance, supplier balances and ordered items awaiting receipt.
- Summary cards and proportional visual bars calculated by database aggregation.
- Dedicated `reports.view` and `reports.profit` permissions.
- Genuine PDF, XLSX and CSV downloads, an A4 print view, and a maximum reporting window of 366 days.

## Delivered settings

- Reference-aligned Company info, Currency & operations, and Branch overrides tabs.
- Business identity, registration, contact, receipt footer, currency, timezone, locale, fiscal year, VAT, tax-inclusive pricing and below-cost selling controls.
- Branch identity and contact details with optional currency, VAT, low-stock and payment-method overrides.
- Separate `settings.view` and `settings.update` authorization at the server boundary.
- Transactional settings updates, business-scoped relationship validation, cross-tenant protection and append-only audit records.

## Delivered localization and operational hardening

- Business-selected English or Kiswahili is applied consistently to the owner dashboard, reports, settings and core Sales/POS checkout.
- The dashboard workspace chooser, empty states, stock warnings and recent-sale labels are translated, while every dashboard amount and chart tooltip uses the selected business currency instead of a hardcoded currency.
- The reference-aligned dashboard appearance switcher persists Classic Sidebar, SmartArt Flow or Component Cards per user and restores it on later visits.
- Classic leaves the existing sidebar experience unchanged. SmartArt connects every accessible module into operational groups. Component Cards uses one compact horizontal group row and expandable single-column module rows with direct links into Sales/POS, catalogue, inventory, purchasing and payment sub-workspaces.
- The top navigation includes a persistent light/dark theme toggle and an English/Kiswahili interface-language toggle; the language preference is session-scoped per user and overrides the business default without changing business settings.
- The top navigation provides the reference Quick Create menu for New Sale, Create Quotation, New Purchase, Receive Stock, Record Expense, Receive Payment and Add Product. Each shortcut requires its corresponding view and create/receive permission and opens the destination directly in creation mode.
- The reference-aligned POS keeps its barcode entry, searchable product grid, held carts and sticky checkout summary while translating the operational controls.
- The Purchases workspace translates draft ordering, suppliers, receiving, returns and supplier-ledger controls.
- The Inventory workspace translates balances, low-stock alerts, opening stock, maker-checker adjustments, physical counts and movement history.
- The Product Catalogue translates product creation and search, category/brand/unit setup, price levels, product lifecycle controls, alternative units and branch reorder thresholds.
- The Payments workspace translates debt collection, payment history, accounts, statements, reversals, aging, credit policy, transfers and cash reconciliation while using tenant currency throughout.
- The Expense register and new-expense workflow translate filtering, entry, account selection, approval steps, posting and reversal controls while using tenant currency throughout.
- Staff and Roles translate team creation, branch and role assignment, account state, disabling evidence, custom roles and permission management.
- Governance translates the approval centre, notification inbox and append-only audit trail, including tenant-currency approval amounts.
- Customer-facing payment receipts, customer statements and sales printouts inherit the tenant locale and currency instead of hard-coding English and TZS.
- Printable A4 and PDF report layouts translate their controls, metadata, table labels, empty state and evidence footer while preserving tenant currency.
- Sales and purchasing amounts use the selected business currency rather than a hard-coded currency label.

## Delivered backup and restore operations

- Checksummed SQLite and MySQL/MariaDB backups in a private configurable directory.
- Daily 02:00 scheduling with overlap protection and configurable retention.
- Guarded restore by exact filename with traversal, checksum and database-driver validation.
- A mandatory pre-restore safety backup before the active database is replaced.
- A tested SQLite restore workflow and a production runbook for maintenance mode, worker shutdown and recovery drills.

## Frontend reference used

The visual hierarchy and interaction patterns were checked against:

- `.frontend-review/src/app/page.tsx`
- `.frontend-review/src/app/components/ClassicDashboard.tsx`
- `.frontend-review/src/app/reports/page.tsx`
- `.frontend-review/src/app/settings/page.tsx`
- `.frontend-review/src/app/sales-pos/page.tsx` and its POS components
- `.frontend-review/src/app/purchases/page.tsx`, `.frontend-review/src/app/purchases/[id]/receive/page.tsx` and `.frontend-review/src/app/suppliers/page.tsx`
- `.frontend-review/src/app/inventory/page.tsx`
- `.frontend-review/src/app/product-management/page.tsx` and its table, filter and detail components
- `.frontend-review/src/app/customers/page.tsx` and `.frontend-review/src/app/customers/[id]/page.tsx` for customer debt and statement patterns
- `.frontend-review/src/app/expenses/page.tsx` and `.frontend-review/src/app/expenses/new/page.tsx`
- `.frontend-review/src/app/staff/page.tsx`

The reference supplies the design direction. HardFlow's Laravel implementation uses live tenant data and server-enforced authorization instead of the reference repository's demonstration data.

## Verification checkpoint

- Database migration applies successfully.
- Phase 8 dashboard, quick-create, report, settings and recovery feature tests: 19 tests, 180 assertions.
- Full automated suite: 112 tests, 542 assertions.
- Blade templates compile successfully.
- Vite production build succeeds.

## Remaining Phase 8 work

- Continue page-level Kiswahili translation beyond navigation, reports, dashboard, settings, Sales/POS, Purchases, Inventory, Product Catalogue, Payments, Expenses, Staff, Roles and Governance.
- Re-run the Composer security advisory audit when Packagist DNS is available.
