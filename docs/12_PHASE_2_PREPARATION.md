# 12 Phase 2 Preparation

## Status

Phase 2 implementation was completed on 2026-09-08. The implemented result and validation evidence are recorded in `13_PHASE_2_IMPLEMENTATION.md`.

Phase 2 covers the business-owned product catalogue and selling-price configuration. It does **not** create or directly mutate inventory quantities.

## Scope

Implement:

- Hierarchical categories.
- Optional brands/manufacturers.
- Reusable business units of measure.
- Products with automatic or manually supplied unique SKUs.
- Product-specific units and conversion factors, including exactly one base unit for stock-tracked products.
- Configurable business price levels, with Retail and Wholesale provisioned as defaults.
- Product selling prices by product unit and price level.
- Branch-specific minimum-stock settings.
- Indexed product search by name, SKU, barcode, category, and brand.
- Policies, focused actions, validation, audit entries, responsive UI, tests, and documentation.

## Explicit phase boundaries

- No stock balances, opening stock, stock movements, stock adjustments, counts, transfers, or low-stock notifications. Those belong to Phase 3.
- No unrestricted quantity field is exposed on a product form.
- No suppliers, purchases, goods receiving, buying-price history, or weighted-average-cost mutation. Those belong to Phase 4.
- No customer-specific price records until customers exist in Phase 5.
- No sale-time price override workflow. Phase 2 provides the catalogue price source; override authorization and transaction snapshots belong to Phase 5.
- Full barcode scanning and label printing remain future features. Phase 2 stores and searches barcode values.

## Proposed data model

Every catalogue table is tenant-owned and contains `business_id`. Tenant relationships use composite constraints where practical so records from different businesses cannot be connected.

### `categories`

- Public UUID, business, optional parent category, name, description, active status, timestamps.
- Name unique within the same business and parent.
- A category cannot parent itself or create a hierarchy cycle.
- A category with children or assigned products is deactivated rather than destructively removed.

### `brands`

- Public UUID, business, name, description, active status, timestamps.
- Name unique within a business.

### `units`

- Public UUID, business, name, symbol, decimal-quantity flag, active status, timestamps.
- Name and symbol unique within a business.
- Examples are Piece/pc, Bag/bag, Kilogram/kg, Metre/m, Box/box, and Roll/roll; examples are not hard-coded platform limits.

### `products`

- Public UUID, business, optional category and brand, SKU, optional barcode, name, description, stock-tracked flag, expiry-tracked flag, active status, timestamps.
- (`business_id`, `sku`) is unique; non-null barcode is unique within the business.
- SKU may be entered by an authorized user or generated through a business-scoped product number sequence.
- Product definitions remain shared by branches.

### `product_units`

- Business, product, unit, conversion factor to the base unit, base-unit flag, purchase-use flag, sale-use flag, timestamps.
- One row per product/unit.
- Conversion factor uses `DECIMAL(18,6)`, is positive, and means `1 selected unit = factor × base unit`.
- A stock-tracked product has exactly one base unit and its base conversion factor is exactly `1`.
- Unit/conversion changes that would invalidate historical transaction meaning must be blocked once future transactions reference the row; records should then be deactivated instead.

### `price_levels`

- Public UUID, business, name, code, description, active status, default status, timestamps.
- Name and code unique within a business.
- Retail and Wholesale are provisioned for each business, but additional levels are allowed.

### `product_prices`

- Business, product unit, price level, amount `DECIMAL(18,2)`, currency code, effective timestamp, optional end timestamp, active status, creator, timestamps.
- Prices attach to a sellable product unit so a box price does not require lossy derivation from a piece price.
- Price history is retained by ending the prior price and creating a new current record; a changed price is not silently overwritten.
- Exactly one current active price is permitted for a product-unit/price-level combination.
- Selling-below-cost enforcement happens when a known cost exists in later purchasing/sales phases; Phase 2 must not invent a cost.

### `product_branch_settings`

- Business, branch, product, minimum stock quantity `DECIMAL(18,4)`, timestamps.
- Unique per branch/product and protected by composite tenant constraints.
- This stores only a reorder threshold; it is not a stock balance.

## Application design

- Resolve business and branch from `TenantContext`; do not accept a browser-supplied business as authority.
- Use policies for category, brand, unit, product, price-level, price, and minimum-stock operations.
- Keep controllers/Livewire components thin. Domain changes use actions such as `CreateProductAction`, `UpdateProductAction`, `AssignProductUnitAction`, and `ChangeProductPriceAction`.
- Wrap product creation with its base unit and initial prices in one database transaction.
- Audit product creation/update, SKU/barcode changes, unit conversion changes, price changes, activation changes, and minimum-stock changes.
- Product price audit data records old/new values, actor, business/branch context, and timestamp. A reason is required for changes to an existing price.
- Avoid N+1 queries and use pagination; the catalogue target is 10,000+ SKUs.
- Search uses normalized input and indexed exact/prefix matches for SKU and barcode plus practical name/category/brand filtering. Advanced full-text infrastructure is not required in this phase.

## Permissions

Use the existing catalogue permissions:

- `products.view`
- `products.create`
- `products.update`
- `products.change_price`

`products.update` controls category, brand, unit, product metadata, conversions, price-level configuration, and minimum-stock settings unless a narrower permission is approved later. `products.change_price` exclusively controls price creation/change.

The Shop Owner receives all permissions through existing provisioning. Other default roles remain configurable by the Owner because no authoritative default role map is documented.

## Delivery order

1. Add enums/value conventions and migrations with tenant-safe constraints and indexes.
2. Add models, relationships, casts, route binding, factories, and seed provisioning updates.
3. Add policies and tenant-tampering tests.
4. Add catalogue actions and unit/base-unit invariants.
5. Add price-level provisioning and append-only price history workflow.
6. Add branch minimum-stock settings without stock balance mutation.
7. Add paginated product search and responsive management UI, following the approved HardFlow visual language.
8. Run the full regression/acceptance suite, formatting, Blade compilation, and production asset build.
9. Record the implemented design and acceptance evidence before Phase 3.

## Mandatory acceptance tests

1. Business A cannot view, update, relate, or price Business B catalogue records by changing request identifiers.
2. An authorized Owner can create categories, brands, and units within the current business.
3. Category hierarchy rejects self-parenting, cycles, and cross-business parents.
4. Product creation atomically creates a valid base product unit and rejects duplicate business SKUs/barcodes.
5. Automatic SKU generation is unique and business-scoped; manual SKU entry remains supported.
6. A stock-tracked product cannot end with zero or multiple base units.
7. Alternative-unit conversion factors are positive, product-specific, and stored precisely.
8. Retail and Wholesale price levels exist for newly provisioned businesses, and custom levels can be created.
9. Price changes require `products.change_price`, retain history, require a reason, and create an audit entry.
10. Product searches find authorized tenant products by name, SKU, barcode, category, and brand without leaking other tenants.
11. Minimum-stock values are branch-specific and cannot reference another business's branch or product.
12. No Phase 2 operation creates or directly changes an inventory balance.
13. Disabled businesses/users remain unable to perform protected catalogue operations.
14. A 10,000-product pagination/search dataset remains usable and avoids unbounded catalogue loading.

## Decisions fixed for implementation

- Catalogue definitions, units, price levels, and prices are business-scoped; products are shared across that business's branches.
- Minimum-stock thresholds are branch-scoped.
- Selling prices are stored per product unit and price level.
- Price history is retained rather than overwritten.
- SKU and barcode uniqueness is scoped to a business.
- Catalogue records referenced by business history will use deactivation instead of destructive deletion.
