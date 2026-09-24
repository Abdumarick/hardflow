# 17 Phase 6 Preparation

## Status

Phase 6 is implemented. Registered and walk-in payments, allocations, reversals, account transfers, customer debt and aging, cash reconciliation, printable receipts and statements, and configurable credit-limit behavior are installed. Phase 7 now supplies the reusable approval engine used by the `require_approval` credit policy.

## Implemented foundation

- Owner-configurable payment methods and branch payment accounts.
- Cash, mobile-money, bank, and card method defaults for new and existing businesses.
- Confirmed registered-customer sales post immutable customer-ledger debits.
- Customer payments can be partially or fully allocated to one or more confirmed sales.
- Sale payment status is derived from confirmed allocations.
- External references are enforced for methods that require them.
- Customer credit-limit blocking and optional sale due dates.
- Aging-ready due-date and customer-ledger schema.
- Account-transfer and cash-session/reconciliation schema foundations.
- Reference-aligned Payments workspace with debt KPIs, unpaid invoices, allocation forms, account cards, and payment history.
- Audited payment reversal that preserves allocations and reopens invoice balances.
- Maker-checker account transfers with source-balance validation.
- Cash-session opening, expected-cash calculation, closing, and explained variance handling.
- Printable customer statements with chronological running balances.
- Walk-in sale payments with compact printable receipts modeled on the reference POS receipt component.
- Configurable warn, require-approval, and block credit-limit policies.
- Current, 1–30, 31–60, and 61+ day aging buckets derived from invoice due dates.
- Sale cancellation credits customer debt, while sales with confirmed payments require payment reversal first.
- 80 automated tests passing with 268 assertions.

## Non-negotiable rules

1. Customer debt is derived from ledger entries and allocations, never a manually editable debt field.
2. Payment and fulfillment statuses remain independent.
3. Allocations cannot exceed either the payment amount or an invoice's outstanding amount.
4. Confirmed payments are immutable and must later use audited reversal entries.
5. Every non-cash reference requirement is controlled by the selected payment method.
6. Transfers and cash differences require traceable decisions and reasons.

## Phase 7 integration points

- Connect `require_approval` credit-limit decisions to the reusable approval engine.
- Apply approved customer-return credits or refunds to the customer ledger and payment accounts.
- Add notifications for overdue balances, rejected transfers, and cash differences.
