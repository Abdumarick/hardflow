# 13 Phase 2 Implementation

## Status

Phase 2 is complete. Product catalogue and pricing are implemented without introducing inventory balances or direct stock mutation. Phase 3 may begin only while the complete regression suite remains green.

## Implemented catalogue

- Business-scoped hierarchical categories, brands, units, products, product units, price levels, and prices.
- Branch-scoped product minimum-stock settings.
- Public UUID route keys for catalogue records exposed in URLs.
- Composite tenant constraints prevent cross-business relationships.
- Business-unique SKU and non-null barcode values.
- Automatic business product references through a locked number sequence, with manual SKU support.
- Exactly one base unit created atomically with each product.
- Positive `DECIMAL(18,6)` product-specific alternative-unit conversions.
- Retail and Wholesale default price levels for new and existing businesses, with custom levels supported.
- `DECIMAL(18,2)` selling prices attached to product units and price levels.
- Price history retained by ending the prior price and creating a new current price.
- A database unique slot guarantees at most one current price per product-unit/price-level combination.

## Security and audit behavior

- Catalogue access uses `products.view`, `products.create`, `products.update`, and `products.change_price`.
- Policies compare route-bound products with the server-resolved tenant context.
- Catalogue reference inputs and product relationships are revalidated against the active business.
- Direct endpoint calls without permission and cross-tenant URL tampering are rejected.
- Product creation/update, unit assignment, price changes, minimum-stock changes, category changes, and product activation changes are audited.
- Price changes and product activation/deactivation require a reason.
- Products are deactivated rather than deleted, preserving future historical references.

## Hierarchy and search

- Category edits reject self-parenting, descendant cycles, and cross-tenant parents.
- Duplicate category names at the same hierarchy level are rejected.
- Product search is tenant-scoped across name, SKU, barcode, category, and brand.
- Results are paginated and tested against a 10,000-product catalogue fixture.

## User interface

The Owner catalogue workspace provides:

- Product creation with optional automatic SKU.
- Category, brand, unit, and price-level setup.
- Category hierarchy editing.
- Product metadata editing and reasoned activation/deactivation.
- Alternative-unit conversion setup.
- Selling-price changes with retained history.
- Branch minimum-stock thresholds clearly separated from actual inventory.
- Responsive search and paginated product listing.

The UI follows the approved HardFlow visual language while all authority remains in Laravel policies/actions.

## Product Management reference expansion

- The primary Products tab now follows the approved repository screen with its action bar, four live KPI cards, expandable filters, detailed price/stock/status columns, selectable rows and configurable pagination.
- Filters cover search, category, brand, stock state, product state and price level while remaining business- and selected-branch-scoped.
- CSV export supports the whole catalogue or only the products selected in the table and neutralizes spreadsheet formula prefixes.
- CSV import provides a downloadable template and atomically creates products, missing category/brand references, Retail/Wholesale prices, selected-branch opening stock and reorder thresholds through existing authorized domain actions.
- Import files are limited to 5 MB and 5,000 products; invalid headers, units, prices or later rows roll back the entire import.
- The importer accepts Excel UTF-8 BOM files and blank lines, rejects duplicate, empty or unsupported columns and surplus cells, validates all numeric precision before writing, and identifies the failing CSV row.
- Import visibility and the upload endpoint require product-create access; price and opening-stock values additionally pass their existing permission checks.
- CSV uploads now use a review-first workflow: analysis creates no products, reports total/complete/incomplete/blocked counts, displays complete rows, and waits for explicit approval or rejection.
- Missing Retail or Wholesale prices are importable warnings rather than blockers, allowing catalogue details and prices to be completed later.
- Products support an optional picture uploaded or replaced after creation; pictures are not required in the CSV.
- CSV approval performs tenant-scoped upserts: it matches existing products by SKU, then barcode, then exact name + category + brand, and separates new, changed and already-current rows in the review.
- Existing metadata, category, brand, prices and minimum-stock values update only when the CSV supplies a different value; blank optional cells preserve existing data.
- An imported quantity for an existing product is treated as a desired physical balance and creates one pending, audited stock-adjustment request instead of overwriting inventory or duplicating an already-pending request.
- Analytics, Import and Add Product open reference-aligned overlays, while Manage connects each product to the existing audited metadata, activation, unit, pricing and threshold tools.
- English and Kiswahili labels are provided for the expanded workspace.

## Phase boundary preserved

No `stock_balances`, `stock_movements`, opening-stock, adjustment, count, transfer, or low-stock notification feature was created. Buying-price history and weighted-average costing remain in Phase 4, where accepted goods receipts provide their source data. Customer-specific pricing and sale-time overrides remain with later customer/sales phases.

## Acceptance evidence

The Phase 2 suite covers:

- Default provisioning and product sequences.
- Atomic product/base-unit creation.
- SKU/barcode uniqueness.
- Cross-tenant relationship and URL tampering rejection.
- Permission-denied direct endpoints.
- Unit precision and positive conversions.
- Required price reasons, retained history, and one-current-price behavior.
- Category hierarchy cycle protection.
- Audited product update/deactivation.
- Branch-specific minimum-stock settings without inventory mutation.
- Tenant-scoped multi-field search over 10,000 products.

Final test/build totals are recorded after the closing full-suite validation.
