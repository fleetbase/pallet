> v0.0.2 ~ "A working warehouse: stock-safe operations, a consumable API and a rebuilt console"

---
## Highlights

v0.0.1 was a scaffold. v0.0.2 is the first release of Pallet that runs end to end: products, warehouses, stock, purchase and sales orders, and the operations in between, with the stock arithmetic guarded and the console rebuilt on the same patterns as the other Fleetbase extensions.

- **Consumable API.** A versioned public API for end consumers alongside the console's internal one, following the FleetOps and Storefront conventions: organization API credentials with bearer auth, records addressed by public id, no internal uuids in responses. It covers products and variants, warehouses, locations and stock, purchase orders and receiving, sales orders and fulfillment, adjustments, transfers, suppliers, batches and the audit trail. The README documents every endpoint, including what the API deliberately will not do.
- **Storefront inventory integration.** Pallet is the canonical inventory authority for Storefront products. Internal endpoints under `pallet/int/v1/storefront/inventory/` resolve, check availability (single and batch), link and unlink Storefront products and variants to Pallet products and variants by durable uuid fields, not by SKU alone.
- **Stock-safe mutations.** Every path that changes stock runs in a transaction with row locks: cancelling a transfer no longer loses stock and completing one no longer destroys it, cycle counts apply atomically, purchase-order receipts are locked and capped against over-receipt, reservations can no longer be released twice, and inventory creation and Storefront linking are transactional with uniqueness enforced. The stock movement ledger is exposed through the API and carries a running balance, and the full quantity set is served on every inventory listing.
- **Operations workflows.** Releasing a wave allocates its pick lists, picking moves stock, expired reservations are swept, and status guards stop invalid transitions. Cycle counts are blind, with an auditable reveal. Purchase orders, sales orders, stock transfers, waves and pick lists each open as a document, the pick list reads as the route a picker walks, and Receive Goods and Fulfill Order report the real reason a request was rejected, with the shortfall named by product. Purchase and sales orders get their own PO- and SO- number series, and partial receipts and fulfillments are audited.
- **Warehouses, zones and bin locations.** Warehouse is now a first-class model with its own table. Zones and bin locations are reachable from the warehouse, batches sort first-expired-first-out, and expiring stock is flagged on the panel header.
- **Dashboard, analytics and reports.** Thirteen metrics endpoints feed twenty-one widgets, the KPI tiles lead to the screens they summarise, unvalued stock is no longer valued at zero, and the low-stock screen agrees with the low-stock KPI. Pallet's tables are registered with the report builder, with a set of starter reports seeded.
- **Console rebuilt.** Every resource sits on the standard list, panel and overlay patterns, documented in `UI_CONVENTIONS.md`: expandable rows for pick lists and count sheets, a line item editor on orders, a rebuilt product form and details panel, pinned row-action columns, placeholder product images, and panels that fit the window. Sidebar navigation, the operations screens, widgets, facilities and inventory are translated.
- **Tenant scoping and validation.** Every tenant-owned listing is scoped to the authenticated company, console creates are validated instead of accepting empty records, unique columns are validated before MySQL answers with a stack trace, and the audit resource no longer leaks internal ids in its snapshots.
- **Audit trail.** The audit model is a WMS operational audit trail with an identifiable subject, a working diff and timestamps, and a deleted record reads as deleted rather than as a row of dashes.
- **Toolchain.** The addon runs on Ember 5.4 with ember-engines and Node 22. The server has a Pest 2 test harness on Testbench, static analysis, and php-cs-fixer, and CI runs lint, tests and build on pull requests into main and release branches. Merging a release branch tags and publishes the release automatically.

---
## Upgrading

- Node 22 or later. The addon needs `@fleetbase/ember-core` ^0.3.22 and `@fleetbase/ember-ui` ^0.3.36.
- The server needs `fleetbase/core-api` and `fleetbase/fleetops-api`. Run migrations after updating: this release adds the `pallet_warehouses` table and new quantity, ledger and Storefront link columns.
- Storefront checkout enforcement should use the explicit `storefront_product_uuid` and `storefront_variant_uuid` link fields; SKU and barcode matching are for assisted lookup only.

---
## Need help?
- [GitHub Discussions](https://github.com/fleetbase/fleetbase/discussions)
- [Discord](https://discord.gg/HnTqQ6zAVn)
