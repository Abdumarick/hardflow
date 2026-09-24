# 06 Workflows

## Business onboarding
Super Admin -> Create Business -> Create Main Branch -> Assign Owner -> Create/assign default roles -> Default settings/number sequences -> Owner access.

All steps must run transactionally so a partially created business is not left behind.

## Purchase and receiving
Purchase Draft -> Ordered -> Goods Arrive -> Goods Receipt -> Check/Inspection -> Approval/Confirmation -> Accepted Stock Movement -> Stock Balance Updated.

Partial receiving is allowed. Ordered, received, damaged, accepted and outstanding quantities remain visible.

## Quotation to sale
Quotation -> Customer confirmation -> Convert to Sale -> Sale processing.

Quotation does not reduce stock.

## Sale, payment and goods release
Sale Draft -> Confirmed -> Payment/Credit handling and Goods Release handled independently.

Examples:
- PAID + ON_HOLD: money received, goods not yet released.
- UNPAID + RELEASED: approved credit sale; goods physically left stock.
- PARTIALLY_PAID + PARTIALLY_RELEASED: both flows are incomplete.

Goods Release -> Stock Movement -> Stock Balance.
Payment -> Payment Account/Allocation -> Customer Ledger/Outstanding balance.

## Stock adjustment
Physical Count -> Compare System vs Physical -> Adjustment Request -> Reason/Evidence -> Approval -> Stock Movement -> Stock Balance.

No direct UI edit of stock quantity.

## Customer return
Original Sale/Release -> Return Request -> Check/Inspection -> Approval -> classify returned goods -> Available/Damaged/Under Inspection stock movement -> financial adjustment/refund/credit as applicable.

## Supplier return
Original Purchase/Receipt where practical -> Return Request -> Approval -> Supplier Return -> Stock Movement Out -> supplier financial adjustment if applicable.

## Expense
Expense Draft/Request -> Approval when required -> Posted -> Payment Account affected -> Audit.

## Cash session
Open Session with Opening Cash -> Cash Transactions -> Expected Closing -> Cashier enters Actual Closing -> Difference -> Reason -> Approval if required -> Close Session.

## Cancellation/reversal
Confirmed transaction -> Cancellation/Reversal Request -> Reason -> Approval according to rule -> compensating/reversal records -> original record retained -> audit log.
