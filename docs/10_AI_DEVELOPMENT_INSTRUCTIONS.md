# 10 AI Development Instructions

This file is mandatory reading for Devin, Codex, or any AI/developer working on this repository.

## Source of truth
1. Read `README.md` and all relevant files under `docs/` before changing code.
2. Treat `03_BUSINESS_RULES.md` as authoritative for business behavior.
3. Treat `09_DEVELOPMENT_ROADMAP.md` as authoritative for implementation order.
4. If documentation conflicts or a business rule is unclear, stop and surface the ambiguity. Do not silently invent a rule.
5. Update documentation when an approved architectural/business decision changes.

## Scope control
Implement only the active roadmap phase unless a prerequisite is required. Do not build future features merely because they are mentioned in the documentation.

Initial instruction: implement Phase 0 and Phase 1 only. Do not start Products, Inventory, Purchases or Sales until Phase 1 acceptance tests pass.

## Mandatory engineering rules
- Preserve tenant isolation in queries, routes, policies, actions, jobs, exports and reports.
- Never trust client-supplied tenant identifiers for authorization.
- Use Policies/Gates/permissions for server-side authorization; hiding a button is not authorization.
- Keep business logic out of Livewire components/controllers where practical. Prefer focused Actions/Services/domain code.
- Use database transactions for critical multi-record operations.
- Use precise decimal data types for money and quantity.
- Never use FLOAT for money.
- Do not directly mutate inventory outside the stock-movement domain workflow.
- Do not permanently delete confirmed financial/inventory transactions.
- Maintain auditability for sensitive changes.
- Protect concurrent stock operations from overselling/race conditions.
- Keep migrations, models, tests and docs synchronized.
- Avoid unnecessary packages and speculative abstractions.

## Suggested application structure
`app/Actions`, `app/Enums`, `app/Livewire`, `app/Models`, `app/Policies`, `app/Services`, `app/Support`.

Examples of future actions include `CreateBusinessAction`, `CreateSaleAction`, `ConfirmSaleAction`, `ReleaseGoodsAction`, `RecordPaymentAction`, `ReceiveGoodsAction`, `RequestStockAdjustmentAction`, and `ApproveStockAdjustmentAction`.

## Phase 1 implementation notes
Use separate concepts for global user identity, business membership, branch access and business-context role assignment. Do not simply put a single `business_id` and single permanent role on the `users` table.

Creating a business should be atomic: create business, main branch, owner membership/role, defaults/settings and number sequences in one database transaction.

A tenant context should expose the authorized current Business and Branch. Server-side code should derive tenant scope from authenticated context whenever possible.

## Required testing mindset
Every security rule must be tested from the perspective of an unauthorized user, not only a happy-path owner. Include cross-tenant ID tampering tests and direct endpoint/action authorization tests.

## Completion standard
A feature is not complete merely because its UI works. It is complete when schema, domain logic, authorization, validation, audit behavior where required, tests and documentation are coherent.
