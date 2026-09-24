# 14 Phase 3 Implementation

## Status

Phase 3 is complete. Inventory is branch-scoped, movement-driven, auditable, and protected against negative balances and stale approvals.

## Implemented

- Separate balances for Available, Damaged, Expired, Returned, and Under Inspection stock.
- Immutable stock movements with before/after balances, actor, reason, source, status, type, and timestamp.
- Atomic movement-and-balance transactions using row locks and revalidation.
- Opening-stock movements for migration into HardFlow.
- Stock-adjustment requests based on system-versus-physical comparison.
- Separate approval/rejection; requesters cannot approve their own adjustments.
- Stale requests are rejected when stock changes after the comparison.
- Physical stock counts snapshot all active stock-tracked products and statuses.
- Count differences create pending adjustments and never directly change balances.
- Branch minimum-stock warnings based only on Available stock.
- Responsive balance, opening-stock, adjustment, count, and movement-history UI.
- Fine-grained inventory permissions provisioned for existing and future businesses.

## Integrity boundaries

Ordinary application code has no direct stock quantity editor. All quantity changes pass through `ApplyStockMovementAction`. Negative post-movement balances are rejected. Cross-business products, branches, counts, and adjustments are rejected through tenant context, composite database constraints, permissions, and endpoint tests.

Phase 3 does not implement purchasing receipts, sales goods release, returns, weighted-average-cost updates, or transfers. Later phases will call the shared movement action from their authorized workflows.

## Acceptance coverage

- Atomic opening balance and movement creation.
- Movement update/delete immutability.
- Negative-stock rejection.
- Adjustment request/approval separation.
- Self-approval rejection.
- Stale concurrent-state rejection with locked balance revalidation.
- Physical count to pending adjustment flow.
- No balance change before approval.
- Cross-tenant URL tampering rejection.
- Low-stock and status-separated balance presentation.
