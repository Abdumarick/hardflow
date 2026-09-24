# 04 Roles and Permissions

## Security domains
Platform authority and business authority are separate.

### Platform
**Super Admin** manages businesses, platform configuration, platform-level users/operations and support/audit access. Super Admin access to tenant transaction data must be controlled and audited.

### Business default roles
- Shop Owner
- Manager
- Cashier
- Storekeeper
- Accountant
- Salesperson

A business owner may create custom roles such as Senior Cashier or Warehouse Supervisor.

## Permission model
Use fine-grained action permissions. Examples:

- `sales.view`
- `sales.create`
- `sales.confirm`
- `sales.cancel`
- `sales.override_price`
- `sales.view_profit`
- `customers.view`
- `customers.create`
- `customers.update`
- `products.view`
- `products.create`
- `products.update`
- `products.change_price`
- `inventory.view`
- `inventory.receive`
- `inventory.adjust.request`
- `inventory.adjust.approve`
- `purchases.view`
- `purchases.create`
- `purchases.receive`
- `expenses.view`
- `expenses.create`
- `expenses.approve`
- `payments.create`
- `reports.sales`
- `reports.profit`
- `users.view`
- `users.create`
- `users.manage_roles`
- `audit.view`

Expand permissions as modules are implemented. Do not use a single broad `manage_everything` permission for normal business roles.

## Membership
A global user account can have explicit membership in one or more businesses and branch access within those businesses. Role assignment belongs to the business context, allowing a user to have different responsibilities in different businesses.

## Approval levels
Approval logic is configurable. Example only:
- Small expense: Manager approval.
- Large expense: Owner approval.
- Stock adjustment: Manager or Owner depending on value/rule.
- Price override beyond a configured threshold: higher approval.
- Sale cancellation: Manager/Owner according to rule.

Do not hard-code monetary thresholds in source code. Store business-specific rules in configuration/approval tables.

## User lifecycle
Users are disabled/inactivated rather than deleted when historical transactions reference them. Historical actor identity must remain intact.
