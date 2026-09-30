# HardFlow Mistake Log

This file records corrected mistakes and avoidable problems so future contributors do not repeat them. Add a new entry whenever a meaningful mistake is corrected.

For each entry, record what happened, its cause, the correction, and a prevention rule. Never include passwords, tokens, private keys, or other secrets.

## 1. Documentation was outside the repository

**Date:** 2026-09-05  
**Phase:** Phase 0 - Foundation

**What happened:** The README referenced `docs/`, but the documentation was stored in a separate Downloads directory connected through the VS Code workspace.

**Correction:** All ten documentation files were placed in the repository-level `docs/` directory, and the workspace was simplified to one project root.

**Prevention rule:** Keep source-of-truth documentation inside the repository. Before implementation, verify that every path referenced by the README exists in the repository.

## 2. Laravel scaffolding was assumed complete too early

**Date:** 2026-09-05
**Phase:** Phase 0 - Foundation

**What happened:** Composer timed out while creating Laravel in `laravel-bootstrap`, leaving an incomplete `vendor/` directory and an abandoned temporary directory.

**Correction:** The scaffold was inspected before use, dependencies were installed cleanly from `composer.lock`, and the verified temporary directory was removed.

**Prevention rule:** After scaffolding, verify that both `artisan` and `vendor/autoload.php` exist before moving or running the application. Remove temporary scaffolding after a successful merge.

## 3. Composer used an invalid saved GitHub token

**Date:** 2026-09-05
**Phase:** Phase 0 - Foundation

**What happened:** Composer received HTTP 401 for a public GitHub archive and fell back to a Windows-incompatible source checkout containing `nul.env`.

**Cause:** Composer had a stale or invalid GitHub OAuth credential in its global authentication configuration.

**Correction:** The invalid `github-oauth.github.com` entry was removed, allowing the public distribution archive to download normally.

**Prevention rule:** When a public GitHub download unexpectedly returns 401, inspect Composer authentication before repeatedly retrying. Never display or commit authentication values.

## 4. Laravel application key was missing

**Date:** 2026-09-05
**Phase:** Phase 0 - Foundation

**What happened:** The first feature test failed with `MissingAppKeyException`.

**Cause:** The interrupted create-project process did not finish its post-create hooks.

**Correction:** `php artisan key:generate` was run before further validation.

**Prevention rule:** After scaffolding or copying Laravel, confirm that `.env` exists and `APP_KEY` is populated before testing the application.

## 5. Frontend commands failed in the restricted Windows environment

**Date:** 2026-09-05
**Phase:** Phase 0 - Foundation

**What happened:** `npm` failed with an `EPERM` error while resolving the parent user directory.

**Cause:** Node needed filesystem access outside the restricted workspace boundary.

**Correction:** The required npm commands were run with the appropriate workspace permission, and the Vite build passed.

**Prevention rule:** If Node reports `EPERM` while resolving a parent path in this environment, do not modify application code as a workaround. Rerun the command with the required filesystem permission.

## 6. Git reported dubious repository ownership

**Date:** 2026-09-05
**Phase:** Phase 0 - Foundation

**What happened:** Git refused commands because the sandbox account and Windows user account did not have matching repository ownership.

**Correction:** Automated inspection used a command-scoped `safe.directory` override instead of silently changing the user's global Git configuration.

**Prevention rule:** Prefer `git -c safe.directory=<absolute-project-path> ...` for automated commands in this environment. Change global Git configuration only with explicit approval.

## 7. Composer lock metadata became outdated

**Date:** 2026-09-05
**Phase:** Phase 0 - Foundation

**What happened:** Project metadata and scripts in `composer.json` changed, causing strict validation to report that `composer.lock` was outdated.

**Correction:** The lock metadata was refreshed and strict Composer validation was rerun.

**Prevention rule:** Run `composer validate --strict` after editing `composer.json`, and refresh `composer.lock` when validation reports a content-hash mismatch.

## 8. Default authentication scaffold allowed hard deletion

**Date:** 2026-09-06
**Phase:** Phase 0 - Foundation

**What happened:** Laravel Breeze generated a profile action that permanently deleted the authenticated user.

**Cause:** Framework starter kits provide generic behavior and do not automatically enforce HardFlow's business rules.

**Correction:** The account-deletion component, profile control, and deletion tests were removed. User disabling will be implemented through the authorized Phase 1 lifecycle.

**Prevention rule:** Review all generated scaffolding against `docs/03_BUSINESS_RULES.md`. Never assume starter-kit behavior is valid business behavior.

## 9. Large patch introduced a malformed test line

**Date:** 2026-09-08
**Phase:** Phase 1 - Multi-tenant access

**What happened:** A large multi-file patch inserted malformed text into an endpoint authorization test.

**Cause:** Too many unrelated additions were grouped into one patch without inspecting the affected file immediately afterward.

**Correction:** The damaged test method was inspected and repaired before running the final suite.

**Prevention rule:** Keep patches focused, inspect every high-risk or large patch boundary, and run syntax/tests immediately after structural edits.

## 10. Tool and patch inputs were not validated first

**Date:** 2026-09-08
**Phase:** Phase 2 preparation

**What happened:** A malformed tool expression was sent, followed by a combined patch that expected a heading not present in the mistake log. The commands did not change application code, and the combined patch was rejected atomically.

**Cause:** The tool input and exact patch context were not verified before execution.

**Correction:** The documentation was reread with a valid read-only command, the exact log context was inspected, and independent changes were split into separate patches.

**Prevention rule:** Validate tool arguments, inspect exact patch anchors, and isolate unrelated file updates when one uncertain context match could reject the entire patch.

## 11. Compressed patches introduced malformed draft tokens

**Date:** 2026-09-08
**Phase:** Phase 2 catalogue implementation

**What happened:** Over-compressed controller and migration drafts contained duplicated or malformed tokens. They were detected before the affected code was migrated or used.

**Cause:** Too many PHP statements and unrelated concerns were compressed into single-line patches, reducing reviewability. A separate read-only tool call also contained an incorrect method name and did not execute.

**Correction:** The controller was replaced with a readable version, the backfill destructuring was corrected before execution, PHP syntax checks were run, and the Laravel formatter expanded compact files consistently.

**Prevention rule:** Keep new domain and controller code readable from the first patch, validate each new file before the next layer, and never submit generated-looking identifiers or tool method names without rereading them.

## 12. MySQL rejected an automatically generated long index name

**Date:** 2026-09-08
**Phase:** Phase 3 inventory

**What happened:** The first MySQL inventory migration stopped because Laravel's generated unique-index name exceeded MySQL's 64-character identifier limit. Three empty tables remained from the partial DDL execution.

**Cause:** Composite index names were left implicit on tables with long names, and SQLite tests did not expose MySQL's identifier limit.

**Correction:** The exact partial tables and row counts were inspected, only the three verified empty Phase 3 tables were removed, concise explicit index names were added, and the migration then completed successfully.

**Prevention rule:** Give concise explicit names to indexes and constraints on long table/column combinations, and validate new migrations against MySQL before declaring a phase installed.

## 13. Phase 3 drafts were over-compressed

**Date:** 2026-09-08
**Phase:** Phase 3 inventory

**What happened:** Early inventory controller, Blade, and test drafts contained malformed tokens introduced while compressing large changes. Validation caught them before final deployment.

**Cause:** Large behavioral and presentation changes were written as dense single-line patches, reducing reviewability.

**Correction:** The controller and inventory view were replaced with readable versions, malformed test tokens were removed, and syntax, Blade compilation, formatting, and focused tests were rerun.

**Prevention rule:** Never compress controllers, domain actions, Blade forms, or security tests into dense one-line patches; write readable code first and validate each file before continuing.

## 14. Initial Phase 4 migration was over-compressed

**Date:** 2026-09-08
**Phase/feature:** Phase 4 purchasing

**What happened:** The initial purchasing migration was written as dense one-line PHP and omitted tenant-safe links from receipt items to purchase items and permission assignment for existing owner roles.

**Cause:** The first schema draft combined too many tables and permission operations without a readable review boundary.

**Correction:** The migration was replaced before deployment with formatted table definitions, composite tenant foreign keys, explicit short indexes, and an owner-role permission backfill. The full suite and a MySQL SQL dry run passed before migration batch 7 was applied.

**Prevention rule:** New multi-table migrations must be readable on first creation and reviewed for tenant keys, existing-data backfills, MySQL identifier limits, rollback order, and production-engine compatibility before execution.

## 15. Duplicate controller methods during endpoint merge

**Date:** 2026-09-08
**Phase/feature:** Phase 4 supplier returns and ledger

**What happened:** Return-decision and supplier-payment controller methods were inserted twice while merging the UI endpoints, causing a PHP redeclaration error.

**Cause:** The controller already contained the endpoint methods from an earlier patch boundary, and the next patch added them again without rereading the full class.

**Correction:** A PHP syntax check caught the duplication before migration. The duplicate block was removed, routes were inspected, Blade was compiled, and the full suite passed.

**Prevention rule:** Before appending controller endpoints, search for each target method name and run `php -l` immediately after the patch.

## 16. Overlapping Phase 5 permission patch duplicated enum cases

**Date:** 2026-09-08
**Phase/feature:** Phase 5 sales foundation

**What happened:** Phase 5 permission cases were present twice after overlapping schema and enum edits, causing PHP to reject the enum during the pre-migration test run. The first sales migration draft also contained an invalid foreign-key method name.

**Cause:** A patch was composed against an incomplete view of changes already applied at the same boundary.

**Correction:** The full enum and migration were inspected, duplicate cases were consolidated, the foreign-key call was corrected, and quotation conversion was strengthened with a tenant-composite relationship. No database change occurred until syntax, all tests, and the MySQL dry run passed.

**Prevention rule:** After every multi-file schema patch, reread each complete enum and migration—not only matching excerpts—before adding follow-up cases or constraints.

## 17. Malformed quotation test patch

**Date:** 2026-09-08
**Phase/feature:** Phase 5 quotation conversion

**What happened:** A quotation conversion test patch contained a duplicated fragment in an array key and an incorrect closing bracket.

**Cause:** The test payload was written as a dense inline array and was not reread before the patch was submitted.

**Correction:** The exact malformed lines were corrected before the test command, then syntax, focused tests, the full suite, and formatting all passed.

**Prevention rule:** Format complex test payloads across readable lines and inspect the changed test method before executing it.

## 18. UI test was coupled to ampersand encoding

**Date:** 2026-09-08
**Phase/feature:** Phase 5 Sales workspace

**What happened:** The Sales workspace rendered successfully, but its smoke test used an encoding-sensitive ampersand expectation and failed despite correct visible output.

**Cause:** The assertion mixed visible-text checking with HTML entity expectations.

**Correction:** The test now verifies stable visible words independently, and focused and complete suites pass.

**Prevention rule:** UI smoke tests should assert meaningful visible text without coupling to HTML entity serialization.

## 19. Sales UI drifted from the prepared frontend reference

**Date:** 2026-09-09
**Phase/feature:** Phase 5 Sales/POS frontend

**What happened:** The first Laravel Sales screen reused the reference colors and rounded cards but remained a generic administration form instead of matching the repository's dedicated POS product-grid and cart workflow.

**Cause:** Backend phase delivery was prioritized while the frontend reference was not reopened before each UI implementation slice.

**Correction:** The GitHub mirror was refreshed and verified at its latest commit, the Sales/POS components were reread, and the Laravel screen was rebuilt around the reference barcode strip, filters, stock-aware product grid, live cart, price levels, discounts, held carts, totals, and transaction history.

**Prevention rule:** Before implementing any user-facing module, refresh the design repository and map its page plus child components into an explicit UI checklist; validate the result structurally and visually before calling the frontend complete.

## 20. Windows development command started an unsupported process

**Date:** 2026-09-09  
**Phase/feature:** Development performance

**What happened:** The default development command started Laravel Pail on Windows even though Pail requires the unavailable `pcntl` extension. Navigation also repeated identical authorization queries for its desktop and mobile layouts.

**Correction:** Removed Pail from the Windows development command, added a Herd-specific command, and cached membership and permission results for the lifetime of each request.

**Prevention rule:** Keep platform-specific development commands free of unsupported processes, and never repeat identical authorization queries within one request.

## 21. Permission-count test was hard-coded

**Date:** 2026-09-09  
**Phase/feature:** Phase 5 customer returns

**What happened:** Adding two valid sales-return permissions broke an older test that expected exactly 35 permission rows.

**Cause:** The test duplicated the enum size as a numeric literal.

**Correction:** The rollback test now captures the seeded permission count before the failing action and verifies that the transaction leaves that count unchanged.

**Prevention rule:** Rollback tests should compare protected reference data with its pre-action state instead of assuming every declared future permission has already been seeded.

## 22. Payment validation used a stale sale model

**Date:** 2026-09-09  
**Phase/feature:** Phase 6 walk-in payments

**What happened:** A newly confirmed sale was rejected by the payment action because the caller still held the earlier in-memory draft instance.

**Cause:** Transaction-state validation happened before reloading and locking the authoritative database row.

**Correction:** Sale-status validation now runs on the row loaded with `lockForUpdate()` inside the payment transaction.

**Prevention rule:** Validate mutable transaction state from the locked database record, not from a model instance supplied by the caller.

## 23. Business-setting test used a hard-coded count

**Date:** 2026-09-09  
**Phase/feature:** Phase 6 credit policy

**What happened:** Adding the credit-limit policy correctly increased default business settings from four to five, breaking an old numeric assertion.

**Cause:** The test checked only a count instead of the required setting keys.

**Correction:** The test now compares the canonical list of default setting keys.

**Prevention rule:** For required configuration catalogues, assert the expected identities rather than only their numeric count.

## Entry template

```markdown
## Short mistake title

**Date:** YYYY-MM-DD
**Phase/feature:** Name

**What happened:** Description.

**Cause:** Root cause.

**Correction:** What fixed it.

**Prevention rule:** Concrete rule for future work.
```
# 24. Keep draft database constraints aligned with the workflow

- Mistake: The first Phase 7 migration made `payment_account_id` and `description` mandatory even though an expense draft may defer both, and its rollback tried to delete permissions before their role links.
- Correction: Make deferred draft fields nullable and delete `role_permissions` links before deleting Phase 7 permissions in `down()`.
- Prevention: Compare migration nullability against every workflow state and test both `up()` and `down()` before considering a schema complete.

# 25. Verify partial patch results before retrying

- Mistake: A multi-file patch reported a later-file mismatch after earlier changes had already landed; retrying those changes duplicated an import, constructor, and notification call.
- Correction: Remove the duplicates and lint the affected file before continuing.
- Prevention: After any partially failed multi-file patch, inspect every targeted file before retrying any section.
# 25. Lint immediately after complex patches

- Mistake: A credit-approval patch introduced a stray diff marker and a malformed array key.
- Correction: Run PHP syntax validation immediately, correct both tokens, and do not continue domain work on an unparsed file.
- Prevention: Keep patches smaller around long inline arrays and lint every changed PHP action before applying dependent changes.

# 26. Avoid framework method names in test helpers

- Mistake: A feature-test helper was named `session`, colliding with the inherited public testing method using a more restrictive visibility.
- Correction: Rename it to `tenantSession` so it expresses its purpose without overriding the framework API.
- Prevention: Before adding helpers to framework test classes, avoid generic names already commonly exposed by the base test case, such as `session`, `get`, `post`, and `json`.

# 27. Check PHP extensions before selecting export packages

- Mistake: The first spreadsheet dependency choice required the unavailable GD extension, so Composer correctly rejected the combined installation.
- Correction: Inspect the active PHP modules and use OpenSpout, which creates genuine XLSX files with the available ZIP/XML extensions; install PDF support independently.
- Prevention: Check PHP version and required extensions before adding format-generation libraries, and never bypass Composer platform checks to force an incompatible package.

# 28. Treat an unreachable advisory service as unresolved

- Mistake avoided: A dependency audit can fail because its advisory service is unreachable, which is not evidence that dependencies are vulnerability-free.
- Correction: Record the JavaScript audit as clean, but explicitly retain the failed Composer audit as an outstanding release check.
- Prevention: Only claim an audit is clean when the advisory command completes successfully; distinguish network failure from a zero-advisory result.

# 29. Verify long single-line Blade replacements immediately

- Mistake: A long inline report-header replacement was truncated and left an incomplete CSS class and HTML structure.
- Correction: Inspect the affected template immediately, then replace the whole corrupted line with a complete known-good header before compiling views.
- Prevention: Format complex Blade sections across multiple lines before editing them, and always inspect plus compile after replacing a long inline template block.

# 27. Check PHP extensions before selecting export packages

- Mistake: The first spreadsheet dependency choice required the unavailable GD extension, so Composer rejected the combined PDF/XLSX installation.
- Correction: Inspect the active PHP extensions and use OpenSpout, which produces genuine XLSX files using the available ZIP and XML extensions; install Dompdf separately for PDF output.
- Prevention: Run `php -m` and review platform requirements before choosing file-generation libraries, and never bypass Composer platform checks to force an incompatible package.

# 27. Check local PHP extensions before selecting export libraries

- Mistake: PhpSpreadsheet was requested before confirming that the active Windows PHP lacked its required GD extension.
- Correction: Keep Composer platform checks enabled and use OpenSpout, which generates genuine XLSX files with the available ZIP and XML extensions.
- Prevention: Run `php -m` and compare required extensions before adding document or spreadsheet libraries; never bypass platform requirements to force an installation.

# 30. Do not hard-code tenant presentation settings

- Mistake: The POS formatter displayed `TZS` directly even though each business has a configurable currency, and its primary checkout labels remained English after tenant locale support was introduced.
- Correction: Pass the selected business currency and translated scanner messages into the Alpine workspace, and render checkout labels from the locale files.
- Prevention: Treat currency, timezone and locale as tenant data at every presentation boundary; add a rendered-page regression test whenever a core workflow is localized.

# 31. Advance CSV iterators after reading the header

- Mistake: The product importer read the CSV header with `fgetcsv()` and then iterated the same `SplFileObject` position, causing the header to be processed as the first product row.
- Correction: Skip row index `0` inside the `foreach` loop because `SplFileObject` iteration rewinds even after an earlier `fgetcsv()` or `seek()`, and verify imports through an uploaded-file feature test.
- Prevention: Every CSV importer must test header handling, a successful multi-field row, and transactional rollback after a later invalid row.

# 32. Import application classes explicitly in namespaced tests

- Mistake: New staff-management tests referenced `TenantContext` without its `App\Support` import, so PHP resolved it under the test namespace and the container could not find it.
- Correction: Add the explicit `use App\Support\TenantContext;` import before evaluating application behavior.
- Prevention: Run the focused test immediately after adding new class references and review its import block before diagnosing a container resolution error as a product defect.

# 33. Align bulk-action visibility with endpoint authorization

- Mistake: The product Import button was visible to catalogue viewers even though importing creates products, and the controller relied only on authorization deeper inside the domain actions.
- Correction: Show Import only to users allowed to create products and authorize the upload endpoint explicitly before parsing the file; price and opening-stock actions continue enforcing their own permissions.
- Prevention: Every bulk UI action must have the same permission guard in both the interface and controller, with domain actions retaining their independent authorization checks.

# 34. Separate import warnings from blocking validation

- Mistake: The original CSV flow combined validation and execution, so users could not inspect a file before database changes or deliberately accept products whose optional prices were not yet known.
- Correction: Split upload analysis from approval, classify missing optional prices as incomplete warnings, keep structurally invalid rows blocked, and create products only after explicit approval.
- Prevention: Bulk imports must provide a dry-run summary and distinguish required identity fields from optional data that can be completed later.

# 35. Dashboard layouts must change the working model, not only the grid

- Mistake: The first dashboard appearance options mostly rearranged the same KPI panels, while Smart Flow showed only a short decorative process strip instead of the modules available in the sidebar.
- Correction: Replace the superficial variants with a permission-aware SmartArt process view and a full component-card view, both generated from the user's accessible modules.
- Prevention: Every appearance option must provide a meaningfully different navigation or information model and must derive visibility from the same permissions as primary navigation.

# 36. Responsive layouts require narrow-phone and short-viewport checks

- Mistake: Several screens used acceptable desktop breakpoints but still forced two-column KPI sections or vertically centred modals on the narrowest and shortest phone viewports.
- Correction: Stack primary metric sections below 640px, constrain dialogs to the dynamic viewport, make overlays scrollable, enforce shrink-safe form controls, and give wide data tables momentum scrolling with a readable minimum width.
- Prevention: Validate every feature at narrow-phone width and short landscape height, including opened dialogs, long forms, wide tables, and permission-dependent navigation—not only the default desktop page.

# 37. Product CSV imports must distinguish duplicates from updates

- Mistake: Existing SKUs and barcodes were treated only as import errors, preventing the catalogue file from safely maintaining product details, prices, thresholds or physical quantities.
- Correction: Resolve existing products by stable identifiers, preview exact changes, preserve blank optional values, skip unchanged rows and route quantity differences through audited stock-adjustment approval.
- Prevention: Before rejecting a bulk-import duplicate, determine whether the format is intended to be create-only or an upsert; never overwrite inventory balances directly and never create duplicate pending adjustments.

# 38. Appearance choices must preserve established navigation

- Mistake: Simplifying the dashboard selector to two choices removed the explicit Classic Sidebar option even though users still needed the established navigation unchanged.
- Correction: Restore Classic Sidebar alongside SmartArt and Component Cards, make card groups compact and expandable, and add persistent theme plus per-session language controls to the top navigation.
- Prevention: When introducing alternative layouts, retain the proven baseline as an explicit choice and ensure every alternative exposes equivalent permission-aware destinations.

# 39. Blade slots render outside nearby Alpine scopes

- Mistake: The dashboard appearance button referenced `appearanceOpen` from a header slot, while that state belonged to a content element rendered elsewhere by the layout, so the visible button could not open the selector.
- Correction: Dispatch a window-level dashboard-appearance event from the header and let the dashboard content scope receive it and open the modal.
- Prevention: Never assume source-code nesting around an `x-slot` becomes DOM nesting; use a shared ancestor store or explicit window events for interactions across layout slots.

# 40. SMS credentials were present in documentation source

- Mistake: The supplied SMS example contained provider credentials directly in a PHP file under `docs/`.
- Correction: SMS configuration now reads the API token, sender ID, endpoint, and enablement flag from environment variables; no credential was copied into application code.
- Prevention: Treat documentation examples as potentially sensitive. Never commit, display, or reuse credentials found in source files; rotate any exposed provider credential.

# 41. Account-recovery behavior did not match the SMS workflow

- Mistake: The initial staff reset interface asked a manager to choose a password, while the public recovery screen still described sending email reset links.
- Correction: The system now generates temporary credentials, sends them only to the registered phone number, accepts email or phone as the recovery identifier, and removes the legacy email-reset route.
- Prevention: When a communication channel changes, audit every related creation, reset, route, screen, status message, and automated test rather than changing only one button.

# 42. Phone-number storage lacked a canonical format and legacy-data backfill

- Mistake: Phone input could be saved in different forms such as `0712345678` or `255712345678`, which makes phone login ambiguous.
- Correction: User phones are normalized to `255XXXXXXXXX` at entry, validated as Tanzanian mobile numbers, indexed uniquely, and existing valid values are backfilled by migration.
- Prevention: Define one stored phone format before enabling phone-based authentication; normalize input server-side and migrate legacy values safely.

# 43. Tenant pages exposed a technical error when no workspace was selected

- Mistake: Opening a tenant-scoped page without session tenant data returned an unhandled HTTP 428 error instead of establishing a usable workspace.
- Correction: Tenant resolution now selects the first accessible active business and branch for the authenticated user and stores that selection in the session.
- Prevention: Authentication flows must establish every required request context before redirecting users to tenant-scoped routes; cover first-navigation behavior with a feature test.

# 44. SMS sender display name was used where the provider requires a sender UUID

- Mistake: The SMS configuration used the visible sender label (`EasyTextAPI`) rather than the approved sender record UUID required by Sewmr's Quick Send endpoint.
- Correction: Retrieve the active sender record from `/sender-ids/`, configure its UUID as `SMS_SENDER_ID`, and verify delivery with the provider response.
- Prevention: Confirm whether an API field expects a display name, internal UUID, or account default directly from the provider's current client/API documentation before testing live delivery.

# 45. Stale Windows Blade-cache files can block view recompilation

- Mistake: A stale temporary compiled-view file in `storage/framework/views` caused Windows to deny Laravel's rename operation while recompiling a Blade view.
- Correction: Clear and rebuild the compiled view cache with `php artisan view:clear` and `php artisan view:cache`, then verify the affected page renders.
- Prevention: When a Windows `rename(...tmp, ...php): Access is denied` error references `storage/framework/views`, treat it as a compiled-view cache issue first; do not change the view logic before clearing the cache.

# 46. CSV imports required a separate re-upload after a missing unit was discovered

- Mistake: The import review reported missing units but gave the user no direct way to register them and continue with the same pending CSV.
- Correction: The review now identifies unique missing units, registers each one in context, and rechecks the securely stored CSV before approval.
- Prevention: When an import depends on business reference data, show the missing references and provide a safe in-flow setup path whenever that data can be created without changing the uploaded rows.

# 47. Missing-unit resolution still required repeated manual actions

- Mistake: The first import-review improvement required the user to register each missing unit separately before the batch could continue.
- Correction: The review now provides one explicit continue action that creates all missing units from the CSV and imports the batch when no other errors remain.
- Prevention: For a repeated, low-risk reference-data gap, offer a clearly labelled bulk continuation action while keeping unsafe row-validation errors blocked.

# 48. Sales product cards ignored uploaded product pictures

- Mistake: The sales workspace sent no product image URL to its POS data and always rendered a placeholder icon.
- Correction: Each sellable product unit now includes a same-site public image path, and POS cards render the uploaded image with a placeholder only when no image exists.
- Prevention: When a page uses client-side product data, include every display field required by the interface and use same-site asset paths where a local development hostname may differ from `APP_URL`; test both the populated and fallback states.

# 49. POS adjustments and product browsing were too rigid

- Mistake: The sales workspace only accepted a percentage discount, kept the cart area unnecessarily tall, and offered only one product-card layout.
- Correction: POS now supports add/deduct adjustments by percentage or TZS amount, a compact/expandable cart, and card/list product browsing.
- Prevention: Core transaction screens should support the common operator choices without forcing manual calculations or wasting working space.

# 50. Walk-in buyers were not recorded in the customer directory

- Mistake: A sale could be saved with only temporary walk-in fields, leaving no linked customer record for later history or follow-up.
- Correction: Every sale now requires a selected customer or a buyer name; unregistered buyers are automatically created or matched in the customer directory and linked to the sale.
- Prevention: Sales workflows must preserve a durable customer identity for every transaction, even when the buyer has not been registered beforehand.
