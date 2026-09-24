# Phase 7 Implementation: Expenses, Returns and Approvals

## Status

Phase 7 is complete. The implementation includes expense management, reusable polymorphic approvals, customer and supplier return controls, reversals, database notifications, and tenant-scoped audit views.

## Delivered workflows

### Expenses

- Business-scoped expense categories are provisioned for existing and new businesses.
- A dedicated reference-aligned New Expense page supports saving a draft or submitting immediately.
- Expense progression is `draft -> pending -> approved -> posted -> reversed`.
- The requester cannot decide their own expense request.
- Posting requires an approved expense, a payment account, and sufficient confirmed account balance.
- Reversal preserves history and restores the derived account balance.
- The register supports status, category, account, and date filters plus tenant-scoped CSV export.

### Shared approval centre

- `approval_requests` uses a polymorphic subject so controlled workflows share one decision history.
- Expense posting, customer returns, supplier returns, credit-limit exceptions, and cash discrepancies are represented in the approval centre.
- Maker-checker rules are enforced server-side.
- Approval decisions and reasons are retained and audited.

### Returns and exceptions

- Approved customer returns restore the inspected stock classification.
- Registered-customer returns post a customer-ledger credit.
- Approved supplier returns reduce classified stock and post a supplier-ledger credit.
- Credit sales exceeding the configured limit can request independent approval.
- Cash sessions with discrepancies remain `pending_approval`; approval finalizes the close and rejection reopens the session for recounting.

### Notifications and audit

- Database notifications alert eligible reviewers and requesters about approval activity.
- Notifications are isolated by user and selected business.
- The audit trail is append-only, business-scoped, filterable, and permission-protected.

## Frontend reference alignment

The GitHub frontend repository was used as the design contract for:

- grouped fixed sidebar and compact top bar;
- Finance, Management, and System navigation hierarchy;
- KPI cards, filters, status chips, responsive tables, and empty states;
- dedicated expense creation layout and workflow preview;
- approval, notification, and audit surfaces where the repository currently only defines navigation destinations.

## Verification

- Full automated suite: 86 tests, 303 assertions.
- Blade templates compile successfully.
- Vite production build succeeds.
- Database migrations apply successfully.

## Phase 8 handoff

Phase 8 begins with dashboard KPIs and core reports. The reference repository currently provides dashboard variants and a reports screen, which should be reviewed before implementation.
