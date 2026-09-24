# Hardware Business Management System (HardFlow)

A multi-business, multi-branch-ready management platform for hardware businesses operating retail and wholesale sales.

## Purpose
The system manages products, units, pricing, inventory, purchases, goods receiving, quotations, sales, goods release, payments, customer debts, expenses, returns, approvals, cash management, audit logs, reports, notifications, and business settings.

## Architecture principles
- Multi-tenant from the foundation: Platform -> Business -> Branch -> Users/Transactions.
- Product definitions belong to a business; stock belongs to a branch.
- Tenant isolation is mandatory.
- Stock changes only through authorized stock movements.
- Physical goods movement is separate from payment status.
- Confirmed financial/inventory transactions are not permanently deleted; use cancellation/reversal/void workflows.
- Sensitive actions use permissions and configurable approvals.
- Important changes are auditable.
- Critical operations use database transactions and concurrency protection.

## Proposed stack
- Laravel
- Livewire + Blade
- Tailwind CSS
- MySQL
- Pest or PHPUnit
- Git/GitHub

## Documentation
Read every relevant file under `docs/` before implementation. Start with `docs/10_AI_DEVELOPMENT_INSTRUCTIONS.md` and `docs/09_DEVELOPMENT_ROADMAP.md`.

## Initial implementation scope
Implement Phase 0 and Phase 1 first. Do not start Products, Inventory, Purchases, or Sales until Phase 1 acceptance tests pass.

## Local development
1. Copy `.env.example` to `.env` and configure the local MySQL credentials.
2. Run `composer install` and `npm install`.
3. Run `php artisan key:generate` and `php artisan migrate`.
4. Create the initial administrator with `php artisan hardflow:create-super-admin you@example.com`.
5. Use `composer run dev` to start Laravel, the queue worker, logs, and Vite.
6. Run `php artisan test` and `vendor/bin/pint --test` before committing changes.

Automated tests use an isolated SQLite in-memory database. MySQL is the application database.

## Project structure
- `app/Actions`: transactional application use cases and state-changing workflows.
- `app/Enums`: statuses and other closed domain values.
- `app/Livewire`: presentation state and user interaction only.
- `app/Models`: Eloquent persistence models and relationships.
- `app/Policies`: server-side authorization rules.
- `app/Services`: reusable domain or infrastructure services.
- `app/Support`: tenant context and small cross-cutting support classes.
- `database/migrations`: schema and integrity constraints.
- `database/factories` and `database/seeders`: deterministic test/development data.
- `docs`: authoritative requirements, business rules, workflows, and roadmap.
- `resources`: Blade, Livewire views, CSS, and JavaScript.
- `tests/Feature`: workflows, authorization, and tenant-isolation coverage.
- `tests/Unit`: isolated domain behavior.

Create module-specific subdirectories only when that module is implemented. Keep controllers and Livewire components thin; business operations belong in Actions or focused Services.

## Frontend reference
The separate [HardFlow design repository](https://github.com/Abdumarick/hardflow) is a visual reference only. Review it when implementing each phase, but do not copy its Next.js runtime, mock authentication, demo credentials, random data, or simulated business behavior into this Laravel application.
