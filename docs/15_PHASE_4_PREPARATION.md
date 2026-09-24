# 15 Phase 4 Preparation

## Status

Phase 4 is implemented. The purchasing, goods-receiving, supplier-return, and supplier-ledger migrations are installed. Phase 3 remains the inventory authority and the complete suite is green.

## Implemented foundation

- Tenant-safe suppliers, purchase orders, purchase lines, goods receipts, receipt lines, and product cost history.
- Draft-to-ordered purchase lifecycle with no stock mutation.
- Draft receipt workflow with server-derived accepted quantity and required short-delivery reasons.
- Atomic receipt confirmation with row locks, over-receipt protection, Available/Damaged stock separation, base-unit conversion, cost history, and weighted-average cost.
- Owner permissions, browser routes, navigation, and a purchasing workspace UI.
- MySQL migration batch 7 applied successfully.
- Supplier returns tied to original purchase lines, with maker-checker approval and stale-stock revalidation.
- Immutable supplier ledger charges, payments, and return credits with balances derived from entries.
- MySQL migration batch 8 applied successfully.

## Completion validation

- 66 automated tests passed with 222 assertions.
- Laravel Pint passed.
- Blade templates compiled successfully.
- Both Phase 4 migrations passed MySQL dry runs and production migrations.

## Scope

- Business-scoped suppliers with optional contact, location, email, and TIN details.
- Branch-scoped purchase orders with multiple product-unit lines and generated internal references.
- Draft and ordered purchase lifecycle without stock mutation.
- Goods receipts with received, accepted, damaged, short, and outstanding quantities.
- Partial receiving across multiple confirmed receipts.
- Buying-price history based on confirmed receipt unit costs.
- Weighted Average Cost updates for accepted Available stock.
- Supplier returns with approval and confirmed stock movement out.
- Supplier charge, payment, return-credit, and adjustment ledger foundations.

## Non-negotiable transaction rules

1. Creating or ordering a purchase never changes stock.
2. A draft goods receipt never changes stock.
3. Confirmation locks the purchase, its lines, current receipt totals, and affected balances.
4. Accepted quantities create `purchase_receipt` movements into Available stock.
5. Damaged quantities create separate movements into Damaged stock and never Available stock.
6. Received-now cannot exceed the line's outstanding quantity; damaged cannot exceed received-now.
7. Short or missing quantities require a difference reason and remain outstanding unless explicitly closed through an auditable workflow.
8. Receipt confirmation, movements, balances, receipt totals, purchase status, buying-price history, and cost updates occur in one database transaction.
9. Confirmed purchases and receipts cannot be edited or deleted; correction uses cancellation/reversal workflows.
10. Supplier outstanding balance derives from ledger entries and allocations, never an editable debt column.

## Costing contract

All receipt quantities convert to the product base unit before inventory calculations. For accepted quantity `q`, existing Available quantity `Q`, current average cost `C`, and accepted base-unit cost `c`:

`new average cost = ((Q × C) + (q × c)) / (Q + q)`

Use precise decimal arithmetic and row locks. Damaged stock does not change the Available-stock weighted average. Preserve every receipt's original product unit, conversion factor, supplied unit cost, and calculated base-unit cost.

## Planned records

- `suppliers`
- `purchases`
- `purchase_items`
- `goods_receipts`
- `goods_receipt_items`
- `product_cost_history`
- `supplier_returns`
- `supplier_return_items`
- `supplier_ledger_entries`

All cross-record relationships carry tenant constraints. Public UUIDs are used for exposed purchases, receipts, suppliers, and returns.

## Permissions

- `purchases.view`
- `purchases.create`
- `purchases.update`
- `purchases.order`
- `purchases.receive`
- `purchases.confirm_receipt`
- `purchases.return.request`
- `purchases.return.approve`
- `suppliers.view`
- `suppliers.create`
- `suppliers.update`

## Required acceptance coverage

1. Purchase creation/order creates no stock movement or balance change.
2. Cross-business supplier, product, unit, purchase, and receipt identifiers are rejected.
3. Multiple product lines and precise unit conversions are retained.
4. Partial receipt updates received/outstanding quantities correctly.
5. Accepted and damaged quantities enter different stock statuses.
6. Short delivery requires a reason and remains traceable.
7. Duplicate confirmation cannot create duplicate stock.
8. Concurrent receipts cannot over-receive a purchase line.
9. Weighted Average Cost is correct across multiple accepted receipts.
10. Buying-price history retains each confirmed source cost.
11. Confirmed receipt history is immutable.
12. Supplier returns require approval and reduce only the classified stock actually returned.
13. Supplier balances derive from ledger entries rather than direct editing.
14. Unauthorized and disabled users/businesses cannot call purchasing endpoints.
