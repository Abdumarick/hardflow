# 11 Phase 1 Implementation

## Status

Phase 1 is implemented. On 2026-09-08, the complete suite passed all mandatory acceptance scenarios: 41 tests with 142 assertions. MySQL migrations, strict Composer validation, Laravel Pint, compiled Blade views, and the production Vite build also passed.

Phase 2 may begin only while these tests remain green.

## Staff account management expansion

- Each manageable staff record now exposes a Manage panel for resetting passwords and assigning business roles and active-branch access.
- Password resets require confirmation and a recorded reason, rotate the remember token, revoke database sessions, and never write the password into an audit record.
- Role assignments remain the source of permissions; user-specific permission exceptions are not introduced.
- Managers receive `users.view` and `users.update` by default, allowing them to manage ordinary staff without permission to edit role definitions.
- Managers cannot manage themselves, manage a Shop Owner, or assign the Owner role. Shop Owners can manage lower roles, while Super Admins can manage business owners after entering that business workspace.
- Password resets and access changes are transactional, tenant-scoped, and recorded as `staff.password_reset` and `staff.access_updated` audit events.

## Implemented model

- A user is a global identity and does not contain a permanent business or business-role column.
- Platform Super Admin authority is stored separately from business roles.
- Business membership is represented by `business_users`.
- Explicit branch access is represented by `branch_users`.
- Roles belong to a business and assignments are represented by `user_roles`.
- Permissions are global definitions attached to business roles through `role_permissions`.
- Composite foreign keys prevent a branch access or role assignment from referencing a different business.
- Public UUIDs are used for business and branch route binding.

## Tenant context

The active business and optional branch are stored in the authenticated server-side session. `TenantContext` reloads and authorizes them for each tenant-protected request. Client-submitted identifiers only request a switch; they never establish authorization.

A normal user must have:

1. An active global account.
2. An active business.
3. An active membership in that business.
4. Explicit active access to the selected active branch.

Super Admin bypass is limited to active platform administrators and is still subject to audit requirements for sensitive operations.

## Business provisioning

`CreateBusinessAction` runs in one database transaction and creates:

- Business and main branch.
- Owner membership and main-branch access.
- Shop Owner, Manager, Cashier, Storekeeper, Accountant, and Salesperson roles.
- Phase 1 permission definitions.
- Full Phase 1 permissions for the Shop Owner role.
- Currency, timezone, locale, and below-cost default settings.
- Branch-scoped sale, purchase, quotation, and payment number sequences.
- A business-created audit entry.

No permission assumptions are assigned to non-owner default roles yet because exact default permission maps were not specified. Owners can configure those roles explicitly.

## User lifecycle

- Public self-registration is disabled.
- Super Admins provision businesses and owners.
- Authorized owners create staff within their current business.
- Disabling staff deactivates that business membership and branch access while retaining the global identity and role history.
- Platform user disabling is a separate Super Admin action.
- Disabled users and businesses are rejected by protected operations.

Create the initial Super Admin interactively:

```bash
php artisan hardflow:create-super-admin admin@example.com
```

The command requires a password of at least 12 characters and does not store a default credential.

## Audit behavior

Business creation/disablement, staff creation/disablement, Super Admin provisioning, custom-role creation, and permission changes create audit entries. Audit entries reject ordinary model update and delete operations.

## Frontend policy

The external Next.js repository is used only as a visual reference. Phase 1 pages reproduce its layout language in native Blade, Livewire, and Tailwind while Laravel remains responsible for authentication, authorization, tenant isolation, validation, and persistence.
