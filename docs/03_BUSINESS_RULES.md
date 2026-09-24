# 03 Business Rules

## Tenancy
1. Every tenant-owned record must be scoped to the current business; branch-specific records must also be scoped to the branch.
2. Never trust a browser-submitted `business_id` as authorization. Resolve tenant context server-side and authorize every action.
3. A user may have access to multiple businesses/branches only through explicit memberships.

## Products and units
1. Each stock-tracked product has a base unit.
2. Alternative units use product-specific conversion factors.
3. Inventory calculations use base-unit quantities.
4. Price levels are configurable by business. Retail and Wholesale are default concepts, not hard-coded limits.
5. Price overrides require permission and an audit record. Record original price, applied price, user, reason and timestamp.
6. Selling below cost is blocked by default.

## Costing
Use Weighted Average Cost initially for stock valuation and cost of goods sold. Preserve purchase/receipt unit costs so costing strategy can evolve without losing source data.

## Inventory
1. UI and ordinary application code must not directly set stock quantities.
2. Every inventory change must be represented by an authorized stock movement.
3. `stock_movements` is the immutable movement history; `stock_balances` is the optimized current balance.
4. Updating movement history and current balance must occur in one database transaction.
5. Stock adjustments require physical/system comparison, reason, requester and required approval.
6. Damaged/expired/under-inspection quantities must not be treated as freely available stock.
7. Protect stock operations against concurrent overselling using transactions, locking and revalidation.

## Purchasing and receiving
1. Creating/confirming a purchase order does not increase stock.
2. Confirmed accepted goods receipt creates the stock movement.
3. Partial receiving is supported.
4. Ordered, received, accepted, damaged and outstanding quantities must remain traceable.
5. Receiving differences require a reason/status such as pending delivery, short delivery or missing.
6. Confirmed purchase/receipt history is not permanently deleted.

## Sales, payments and fulfillment
1. Sale status, payment status and fulfillment status are separate concepts.
2. Stock decreases when goods physically leave controlled stock through a goods release, not merely because payment occurred.
3. Therefore a credit sale may be UNPAID but RELEASED, and stock must already be reduced.
4. A paid sale may remain ON_HOLD if goods have not yet been released.
5. Quotations do not reduce stock. Future stock reservation may be added separately.
6. Goods may be released in more than one release for a sale; each confirmed release creates stock movements.
7. Confirmed/completed sales are not hard-deleted. Use cancellation/void/reversal workflows with reason and approval.
8. Returns must be traceable to the original transaction where practical and inspected before being returned to Available stock.

## Customer debt
1. Do not treat a manually edited `customers.debt` field as the source of truth.
2. Outstanding balance derives from invoices/sales, payments, credits/reversals and allocations.
3. Maintain customer ledger/history for statements and aging.
4. Partial payments are supported.
5. Credit limits are optional and policy can warn, require approval or block.

## Payments and cash
1. Payment methods and payment accounts are separate concepts.
2. Each payment must identify its account and internal payment number; external reference rules depend on method.
3. Confirmed payments are reversed/cancelled, not hard-deleted.
4. Account transfers create traceable financial movements and may require approval.
5. Cash sessions compare expected and actual closing cash; differences require explanation and may require approval.

## Expenses
1. Expenses are linked to the account used to pay them.
2. Where approval is required, an expense does not affect finalized account balances until approved/posted.
3. Posted expenses are reversed/cancelled rather than erased.

## Approvals
1. Use a reusable approval engine rather than module-specific ad-hoc approval code.
2. Rules may depend on action type, amount, permission and role/level.
3. Approval actions are auditable: requested, approved, rejected, returned for correction.
4. A user must not approve an action merely because they created it unless the configured rule explicitly allows it.

## Audit
1. Audit logs are append-only from normal application flows.
2. Record user, business/branch, action, module/reference, old/new values where relevant, reason, IP/device and timestamp.
3. Sensitive Super Admin access to tenant data should itself be auditable.

## Data integrity
1. Use `DECIMAL(18,2)` or an equivalent precise decimal for money; never FLOAT.
2. Use a precise decimal such as `DECIMAL(18,4)` for quantities.
3. Critical multi-record operations use database transactions.
4. Document/reference numbers are separate from database primary keys.
5. Confirmed financial and inventory history must remain reconstructable.
