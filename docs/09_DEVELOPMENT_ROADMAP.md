# 09 Development Roadmap

## Phase 0: Foundation
- Create Laravel application and repository structure.
- Configure MySQL and environments.
- Authentication.
- Livewire/Blade and Tailwind foundation.
- Testing framework.
- Establish Actions/Services, Policies, Enums and Support conventions.
- CI/lint/static-analysis choices as appropriate.
- Read and preserve all documented business rules.

## Phase 1: Multi-tenant access foundation
Implement:
- users
- businesses
- branches
- business memberships
- branch access
- roles
- permissions
- role assignments
- tenant context
- business creation action
- default roles/settings/number sequences
- Super Admin business management
- Owner user/role management

### Mandatory acceptance tests
1. Super Admin can create Business A and Business B.
2. Business creation atomically creates main branch, owner membership and required defaults.
3. Owner A sees only Business A.
4. Owner B sees only Business B.
5. Business A user cannot access Business B by changing a URL/request ID.
6. Branch user cannot access an unauthorized branch.
7. Owner can create/disable staff while historical identity remains.
8. Owner can create a custom role and assign/remove permissions.
9. Users without a permission cannot bypass it by calling an endpoint directly.
10. Disabled business/user cannot perform normal protected operations.

Do not begin Phase 2 until these tests pass.

## Phase 2: Product catalogue and pricing
- Categories and hierarchy
- Brands
- Units
- Products
- Product units/conversions
- Price levels
- Product prices
- Minimum stock settings
- Product search foundations

## Phase 3: Inventory
- Stock balances
- Immutable stock movements
- Opening stock
- Stock adjustments and approval
- Physical stock count foundations
- Low stock alerts
- Concurrency/locking tests

## Phase 4: Purchasing and receiving
- Suppliers
- Purchase Orders/Purchases
- Purchase items
- Goods receipts
- Partial receiving
- Damage/shortage handling
- Weighted average cost updates
- Supplier returns/debt/payment foundations

## Phase 5: Sales and fulfillment
- Customers
- Quotations
- Proforma invoices
- Sales/POS
- Sale items
- Discounts and price overrides
- Goods release and partial release
- Invoices/receipts
- Sales cancellation/return foundations

## Phase 6: Payments, debt and cash
- Payment methods/accounts
- Payments
- Payment allocations
- Customer ledger/statements
- Credit limits/due dates/aging
- Account transfers
- Cash sessions and reconciliation

## Phase 7: Expenses, returns and approvals
- Expense categories/expenses
- Reusable approval engine completion
- Customer/supplier returns
- Reversals/cancellations
- Notifications
- Expanded audit coverage

## Phase 8: Dashboard, reports and operations
- Dashboard KPIs
- Core reports
- PDF/Excel/CSV exports
- Business/branch settings
- Backups/restore procedures
- Localization polish
- Responsive/POS UX polish
- Performance and security review

## Development rule
Complete each phase with migrations, models, authorization, actions/services, UI where required, tests and documentation updates before advancing.
