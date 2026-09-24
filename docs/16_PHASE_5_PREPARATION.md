# 16 Phase 5 Preparation

## Status

Phase 5 is implemented. Customers, direct sales, quotations/proformas, quotation conversion, pricing safeguards, confirmation, partial goods release, cancellation safeguards, customer-return foundations, the browser workspace, and printable sales documents are complete.

## Implemented domain foundation

- Customer and sales models with tenant-scoped relationships.
- Catalogue-price lookup, discounts, authorized price overrides, and weighted-average cost snapshots.
- Below-cost protection through the business setting.
- Sale confirmation without inventory mutation.
- Draft and confirmed partial goods releases with stale-row and duplicate-confirmation protection.
- Audited sale cancellation before physical release; released sales cannot be cancelled without the return workflow.
- Customer returns tied to released original sale lines, with maker-checker decisions, classified restocking, and over-return protection.
- Quotation/proforma creation and direct conversion preserving customer, line, price, and discount snapshots.
- Sales navigation and responsive workspace with customer, quotation, sale, conversion, and goods-release controls.
- Sales/POS rebuilt from the current reference repository structure: barcode/SKU strip, category and text filtering, stock-aware product grid, live cart, quantity controls, price-level selection, percentage discounts, held carts, and calculated totals.
- Printable sale documents suitable for browser and A4 printing.
- 73 automated tests passing with 245 assertions; Blade compilation, formatting, and frontend builds pass.

## Scope

- Registered and walk-in customers.
- Quotations and proforma invoices that never change stock.
- Direct conversion of quotations into sales without re-entering lines.
- Draft and confirmed sales with independent payment and fulfillment statuses.
- Product-unit price snapshots, discounts, authorized overrides, and cost snapshots.
- Goods releases and partial release; only confirmed releases reduce Available stock.
- Invoice/receipt document foundations and cancellation/return-ready references.

## Non-negotiable rules

1. A quotation, proforma, or sale confirmation does not change inventory.
2. Each sale line preserves product, selected unit, conversion factor, original price, applied price, discounts, and cost snapshot.
3. Selling below current average cost is blocked unless the business setting explicitly permits it.
4. A changed applied price requires `sales.override_price`, a reason, and actor audit data.
5. Goods release quantities use base-unit conversion and cannot exceed either the sale outstanding quantity or Available stock.
6. Release confirmation locks the sale, lines, release, and affected stock balances in one transaction.
7. Payment and fulfillment status remain independent.
8. Confirmed sales and releases are never hard-deleted; later corrections use approved reversal workflows.

## Initial permissions

- `customers.view`, `customers.create`, `customers.update`
- `quotations.view`, `quotations.create`, `quotations.convert`
- `sales.view`, `sales.create`, `sales.confirm`, `sales.cancel`
- `sales.override_price`, `sales.view_profit`
- `sales.release`, `sales.confirm_release`

## Required acceptance coverage

1. Quotation and sale confirmation create no stock movements.
2. Quotation conversion preserves line pricing and customer data.
3. Cross-business customer, product, unit, sale, and release identifiers are rejected.
4. Unauthorized price overrides are rejected and authorized overrides are audited.
5. Below-cost sales are blocked by default.
6. Partial releases update released/outstanding quantities without changing payment status.
7. Duplicate or concurrent release confirmation cannot double-release or oversell stock.
8. Sale-time cost snapshots remain unchanged after later purchase-cost changes.
