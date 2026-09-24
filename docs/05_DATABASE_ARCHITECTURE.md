# 05 Database Architecture

This is the planned logical model. Exact migrations may evolve during implementation, but changes must preserve documented business rules.

## Platform, business and access
Core tables:
- `users`
- `businesses`
- `branches`
- `business_users`
- `branch_users`
- `roles`
- `permissions`
- `role_permissions`
- role assignment table such as `user_roles`

Key constraints:
- `business_users`: unique (`business_id`, `user_id`)
- `branches`: unique (`business_id`, `branch_code`)
- branch access must only be granted when the user belongs to the parent business.

Avoid relying solely on `businesses.owner_user_id`; ownership should be represented through membership/role relationships so multiple ownership structures remain possible.

## Products and pricing
- `categories` (`business_id`, optional `parent_id`)
- `brands`
- `units`
- `products`
- `product_units`
- `price_levels`
- `product_prices`

Recommended uniqueness: (`business_id`, `sku`) unique for products.

`product_units` contains product-specific conversion factor, base-unit flag and whether the unit can be used for purchase/sale.

## Inventory
- `stock_balances`
- `stock_movements`
- `stock_adjustments`
- `stock_adjustment_items`
- `stock_counts` / `stock_count_items` when physical counting is implemented
- `stock_transfers` / `stock_transfer_items` in expanded branch operations

Recommended `stock_balances` uniqueness: (`branch_id`, `product_id`).

Stock movement types include PURCHASE_RECEIPT, SALE_RELEASE, CUSTOMER_RETURN, SUPPLIER_RETURN, DAMAGE, EXPIRY, TRANSFER_IN, TRANSFER_OUT, ADJUSTMENT_IN, ADJUSTMENT_OUT, OPENING_STOCK.

## Suppliers and purchasing
- `suppliers`
- `purchases`
- `purchase_items`
- `goods_receipts`
- `goods_receipt_items`
- `supplier_returns`
- `supplier_return_items`

A purchase has many purchase items and may have many goods receipts. Goods receipts are the source of accepted inbound stock movements.

## Customers and sales
- `customers`
- `quotations`
- `quotation_items`
- `sales`
- `sale_items`
- `goods_releases`
- `goods_release_items`
- `sale_returns`
- `sale_return_items`

`sales` stores separate sale, payment and fulfillment statuses. Goods release allows partial physical collection independent of payment.

Sale items preserve original price, applied price/override information, cost snapshot as appropriate and audit metadata.

## Payments and ledger
- `payment_methods`
- `payment_accounts`
- `payments`
- `payment_allocations`
- `account_transfers`
- `customer_ledger`

A payment can be allocated across one or more sales/invoices. Do not maintain an independently editable debt total as the accounting source of truth.

## Expenses
- `expense_categories`
- `expenses`

Expenses reference the paying account and approval/posting status.

## Approvals
- `approval_rules`
- `approval_requests`
- `approval_actions`

One reusable approval engine serves expenses, stock adjustments, cancellations, returns, price overrides, cash differences and other sensitive actions.

## Cash management
- `cash_sessions`

Avoid duplicating transaction data unnecessarily. Payments, expenses and transfers already reference accounts; cash sessions should reconcile the transactions that occurred during the session.

## Audit and system support
- `audit_logs`
- `notifications`
- `business_settings`
- `branch_settings`
- `number_sequences`

Number sequences generate business/branch-scoped references such as `ARU-SAL-000001`, `ARU-PUR-000001`, `ARU-QTN-000001`, `ARU-PAY-000001`.

## Data types
- Money: precise decimal, normally `DECIMAL(18,2)`.
- Quantities: precise decimal, normally `DECIMAL(18,4)`.
- Dates/times: database timestamp/date types, never formatted strings as the stored canonical value.
- Consider public UUIDs/ULIDs for externally exposed records while retaining efficient internal primary keys if desired.

## Indexing
Index high-use tenant/filter columns, including combinations involving `business_id`, `branch_id`, `product_id`, `customer_id`, `supplier_id`, status, transaction date and created_at. Final composite indexes must follow real query patterns and EXPLAIN results rather than indiscriminate indexing.
