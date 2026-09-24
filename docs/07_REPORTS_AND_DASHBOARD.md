# 07 Reports and Dashboard

## Dashboard
Owner dashboard should support Today, Week, Month, Year and Custom Date where meaningful. Show:
- Sales
- Purchases
- Expenses
- Gross profit
- Net profit
- Customer debt
- Payment-account/cash balances
- Low stock
- Top-selling products
- Slow-moving products
- Period comparisons
- Pending approvals/important alerts

Profit and sensitive financial visibility are permission-controlled.

## Core reports
- Daily/period sales
- Purchase report
- Stock on hand
- Product movement
- Stock valuation
- Gross profit
- Net profit
- Expense report
- Customer outstanding debt
- Debt aging (0-30, 31-60, 61-90, 90+ days or configurable buckets)
- Customer statement
- Supplier purchases/payments/statement
- Payment method/account report
- Cash session/reconciliation report
- Low stock
- Fast-moving products
- Slow-moving products
- Salesperson/cashier performance
- Audit/activity report
- Ordered but not yet received purchase items

## Filters
As appropriate to each report: date/custom range, branch, user, customer, supplier, product, category, payment method/account and status.

## Export
Reports should support PDF, Excel and CSV where useful. A4 printing is required. Receipt outputs are handled in transactional document workflows.

## Reporting design rule
Reporting requirements must influence schema/index design from the beginning. Do not build reports solely by loading large datasets into application memory.
