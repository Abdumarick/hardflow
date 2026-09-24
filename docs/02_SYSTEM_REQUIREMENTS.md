# 02 System Requirements

## Business and branches
- Support multiple independent businesses.
- Prepare for multiple branches per business from the foundation.
- Shared business product catalogue with branch-specific stock.
- Support controlled stock transfer between branches in a later/expanded phase.
- One owner may own multiple businesses.
- Business/branch profile data includes name, phone, email, address, TIN/VRN where available, logo/settings and document details.

## Products and pricing
- 10,000+ products/SKUs.
- Automatic or manually assigned SKU/product codes.
- Barcode-ready architecture; full barcode workflow can be phased later.
- Multiple units per product and product-specific unit conversion.
- Base unit used for inventory calculations.
- Retail and wholesale pricing plus owner-defined price levels.
- Customer-specific/special pricing supported.
- Authorized price override with actor, old price, new price, reason, time and transaction recorded.
- Never allow selling below cost by default.
- Maintain buying-price history.
- Use Weighted Average Cost as the initial recommended costing method.
- Product minimum stock, configurable per branch.
- Optional brand/manufacturer.
- Future store/rack/shelf location support.
- Expiry tracking only for products that require it.
- Stock statuses include Available, Damaged, Expired, Returned and Under Inspection.

## Inventory
- No unrestricted direct editing of stock quantity.
- Stock adjustments require a request, strong reason/evidence and approval based on permissions/rules.
- Compare system quantity with physical quantity during stock counting.
- Maintain complete stock movement history.
- Low-stock alerts.

## Purchasing
- Stock enters through purchase/stock-entry and goods-receiving processes.
- Suppliers may be recorded with basic details; full registration is not mandatory.
- Supplier information may include name, phone, location, email and TIN, with nonessential fields optional.
- Purchases can contain multiple products.
- Supplier reference/invoice number optional.
- Internal purchase number generated automatically.
- Buying price captured during purchase/receiving and history preserved.
- Show changes from previous buying price.
- Purchase creation does not immediately increase stock.
- Flow: Purchase Created -> Goods Arrived -> Checked -> Confirmed -> Stock Updated.
- Receiving user/confirming user controlled by permission.
- Record ordered vs received quantity and differences.
- Partial receiving supported.
- Damaged received goods recorded separately and not added to Available stock.
- Attachments on receiving are optional.
- Supplier returns supported, with approval rules and stock decrease after confirmation.
- Supplier debt/payment history supported even if supplier credit is uncommon.
- Confirmed purchases are cancelled/reversed, not permanently deleted.
- Purchase Orders supported.
- Report ordered-but-not-yet-received items.

## Sales, quotations and goods release
- Retail and wholesale sales.
- Walk-in customer name/phone may be recorded but is optional for ordinary sales.
- Credit customers must be registered.
- Multiple products per sale.
- Discounts may be item-level or invoice-level; limits and approval rules apply.
- Quotations are required and must display customer name, phone and location where provided.
- Quotations convert directly to sales without re-entry.
- Quotation validity/expiry is optional/configurable.
- Proforma invoices supported.
- Sale status, payment status and fulfillment status are separate.
- Stock follows physical goods release, not payment alone.
- Completed sales are cancelled/reversed, not deleted.
- Cancellation requires reason and approval according to rules.
- Customer return workflow references original sale where applicable and requires inspection before Available stock.
- Initial requirement did not prioritize partial customer returns; architecture should avoid blocking future support.
- Automatic invoice and receipt numbering, scoped by business/branch.
- Receipt issued after payment.
- Initial Version 1 may keep one payment method per payment transaction; architecture supports multiple payments/allocations later.
- POS is barcode-ready.
- Search products by name, SKU/code, barcode and category.
- Sales may be put On Hold.
- Track the user responsible for sales and price changes.
- Profit visibility is permission-controlled.
- Printing: A4, thermal receipt and future EFT/POS support.

## Payments and customer debt
- Support Cash, Mobile Money, Bank and owner-configurable payment methods. An earlier cash-only response conflicted with the wider requirements; multi-method support is the resolved requirement.
- Separate mobile-money/bank/cash accounts and balances.
- Owner can add payment methods/accounts.
- External transaction/reference numbers may be required or optional by payment method; cash gets an internal reference.
- Debt payments generate receipts.
- Partial debt payments supported.
- One customer may have multiple unpaid invoices.
- Show total outstanding debt and invoice history.
- Optional customer credit limit.
- Credit-limit policy configurable: warn, require approval or block.
- Due dates and overdue alerts.
- Customer statement printable.
- Cash sessions may use opening cash and closing actual cash; this needs final UI refinement but is in scope.
- Compare expected and actual cash; differences require reason and may require approval.
- Money transfers between payment accounts supported with approval rules.
- Expenses affect the selected payment account after required approval/posting.
- Owner can view balances by payment account at any time.
- Payment entry restricted by permission.
- Confirmed payments use reversal/cancellation rather than deletion.

## Expenses
- Owner-defined expense categories.
- Expense linked to payment account/method.
- Receipt/reference optional.
- Expense creation controlled by permission.
- Approval required, with configurable amount/action levels.
- Recurring expenses are a future feature.
- Expense attachments not required in initial scope.
- Confirmed/posted expenses should be reversed/cancelled rather than permanently deleted, despite an earlier answer allowing deletion; audit-safe behavior is the resolved rule.

## Users, roles and security
- Default roles: Super Admin, Shop Owner, Manager, Cashier, Storekeeper, Accountant, Salesperson.
- Owner can create custom business roles.
- Fine-grained action permissions.
- Approval levels and rules based on action, permission and amount.
- User branch/business access is explicitly controlled.
- Users can be disabled without deleting historical transactions.
- Login history and device/IP history.
- Sensitive actions may require re-authentication depending on action.
- Two-factor authentication prepared for later, especially Owner/Super Admin.
- Audit-log visibility is permission-based; Super Admin has platform authority subject to audit.
- Audit logs cannot be deleted through normal application flows.
- Important changes store old/new values.
- Price changes require a reason.

## Reports and dashboard
- Owner dashboard: sales, purchases, expenses, profit, debts, balances, low stock, best-selling and slow-moving products.
- Periods: Today, Week, Month, Year and Custom Date.
- Comparisons with prior periods.
- Gross and net profit.
- Filters by branch, user, customer, supplier, product, category, payment method and custom date as appropriate.
- Export PDF, Excel and CSV.
- Daily sales report.
- Product movement report.
- Stock valuation report.
- Customer debt and aging report.
- Supplier reports/statements.
- Low-stock and overdue-debt notifications.
- Approval notifications.
- Notification architecture supports system, email, SMS and WhatsApp channels over time.
- Future sending of invoices/receipts by WhatsApp/email.
- End-of-day owner summary.

## Operations and settings
- Automatic database backup and configurable frequency.
- Authorized restore mechanism.
- Per-business/per-branch settings.
- Default TZS with multi-currency-ready architecture.
- English and Kiswahili.
- Responsive desktop/tablet/mobile design with POS considerations.
- Offline capability desired; implement architecture readiness first and full synchronization in a later phase unless made mandatory.
