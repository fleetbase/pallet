<p align="center">
    <p align="center">
        <img src="https://github.com/fleetbase/pallet-api/assets/816371/b8f49fe3-4464-4c9a-b296-7f62c2f45d48" width="280" height="280" />
    </p>
    <p align="center">
        Inventory & Warehouse Management Extension for Fleetbase
    </p>
</p>

---

## Overview

This monorepo contains both the frontend and backend components of the Pallet extension for Fleetbase. The frontend is built using Ember.js and the backend is implemented in PHP.

* PHP 8.0 or above
* Ember.js v5.4 or above
* Ember CLI v5.4 or above
* Node.js v22 for CI builds

## Structure

```
├── addon
├── app
├── translations
├── config
├── node_modules
├── server
│ ├── config
│ ├── migrations
│ ├── src
│ └── tests
├── server_vendor
├── tests
├── extension.json
├── testem.js
├── index.js
├── package.json
├── phpstan.neon.dist
├── phpunit.xml.dist
├── pnpm-lock.yaml
├── pnpm-workspace.yaml
├── ember-cli-build.js
├── composer.json
├── CONTRIBUTING.md
├── LICENSE.md
├── README.md
```

## Installation

### Backend

Install the PHP packages using Composer:

```bash
composer require fleetbase/pallet-api
```
### Frontend

Install the Ember.js Engine/Addon:

```bash
pnpm install @fleetbase/pallet-engine
```

## Consumable API

Pallet exposes a versioned public API for end consumers alongside the console's internal one. It follows the same conventions as the FleetOps and Storefront APIs: requests authenticate with an organization API credential, records are addressed by public id, and responses never carry internal uuids.

```text
https://<your-fleetbase-host>/pallet/v1/...
```

### Authentication

Every route requires an API credential, supplied as HTTP basic auth with the key as the username:

```bash
curl https://api.fleetbase.io/pallet/v1/products \
  -u "$FLEETBASE_API_KEY:"
```

The credential resolves the company, and every listing, lookup and write is scoped to it. A record belonging to another company responds `404` rather than `403` — an id you cannot see is indistinguishable from one that does not exist.

### Conventions

* **Identifiers.** `id` is the public id (`product_a1b2c3`). Related records are both accepted and returned as public ids, so a consumer never handles a uuid.
* **Errors.** `{"error": "Product resource not found."}` with a conventional status code.
* **Deletes.** `{"id": "...", "object": "product", "deleted": true, "time": "..."}`.
* **Listings** accept `limit` (max 100, default 30), `offset` or `page`, `sort`, and per-column filters.
* **Object type.** Every resource carries an `object` field naming its type.

### Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `pallet/v1/products` | Create a product |
| `GET` | `pallet/v1/products` | List products |
| `GET` | `pallet/v1/products/{id}` | Retrieve a product |
| `PUT` `PATCH` | `pallet/v1/products/{id}` | Update a product |
| `DELETE` | `pallet/v1/products/{id}` | Delete a product |
| `POST` | `pallet/v1/product-variants` | Create a variant against a product |
| `GET` | `pallet/v1/product-variants` | List variants |
| `GET` | `pallet/v1/product-variants/{id}` | Retrieve a variant |
| `PUT` `PATCH` | `pallet/v1/product-variants/{id}` | Update a variant |
| `DELETE` | `pallet/v1/product-variants/{id}` | Delete a variant |
| `POST` | `pallet/v1/suppliers` | Create a supplier |
| `GET` | `pallet/v1/suppliers` | List suppliers |
| `GET` | `pallet/v1/suppliers/{id}` | Retrieve a supplier |
| `PUT` `PATCH` | `pallet/v1/suppliers/{id}` | Update a supplier |
| `DELETE` | `pallet/v1/suppliers/{id}` | Delete a supplier |
| `POST` | `pallet/v1/warehouses` | Create a warehouse |
| `GET` | `pallet/v1/warehouses` | List warehouses |
| `GET` | `pallet/v1/warehouses/{id}` | Retrieve a warehouse |
| `PUT` `PATCH` | `pallet/v1/warehouses/{id}` | Update a warehouse |
| `DELETE` | `pallet/v1/warehouses/{id}` | Delete a warehouse |
| `POST` | `pallet/v1/warehouse-zones` | Create a zone in a warehouse |
| `GET` | `pallet/v1/warehouse-zones` | List zones |
| `GET` | `pallet/v1/warehouse-zones/{id}` | Retrieve a zone |
| `PUT` `PATCH` | `pallet/v1/warehouse-zones/{id}` | Update a zone |
| `DELETE` | `pallet/v1/warehouse-zones/{id}` | Delete a zone |
| `POST` | `pallet/v1/bin-locations` | Create a bin location |
| `GET` | `pallet/v1/bin-locations` | List bin locations |
| `GET` | `pallet/v1/bin-locations/{id}` | Retrieve a bin location |
| `PUT` `PATCH` | `pallet/v1/bin-locations/{id}` | Update a bin location |
| `DELETE` | `pallet/v1/bin-locations/{id}` | Delete a bin location |
| `GET` | `pallet/v1/inventory` | List stock records |
| `GET` | `pallet/v1/inventory/availability` | How much of a product can be committed |
| `GET` | `pallet/v1/inventory/{id}` | Retrieve a stock record |
| `POST` | `pallet/v1/stock-adjustments` | Adjust stock |
| `GET` | `pallet/v1/stock-adjustments` | List adjustments |
| `GET` | `pallet/v1/stock-adjustments/{id}` | Retrieve an adjustment |
| `POST` | `pallet/v1/purchase-orders` | Create an order with its lines |
| `GET` | `pallet/v1/purchase-orders` | List purchase orders |
| `GET` | `pallet/v1/purchase-orders/{id}` | Retrieve a purchase order |
| `PUT` `PATCH` | `pallet/v1/purchase-orders/{id}` | Update a purchase order |
| `DELETE` | `pallet/v1/purchase-orders/{id}` | Delete a purchase order |
| `POST` | `pallet/v1/purchase-orders/{id}/receive` | Receive stock against lines |
| `POST` | `pallet/v1/sales-orders` | Create an order with its lines |
| `GET` | `pallet/v1/sales-orders` | List sales orders |
| `GET` | `pallet/v1/sales-orders/{id}` | Retrieve a sales order |
| `PUT` `PATCH` | `pallet/v1/sales-orders/{id}` | Update a sales order |
| `DELETE` | `pallet/v1/sales-orders/{id}` | Delete a sales order |
| `POST` | `pallet/v1/sales-orders/{id}/fulfill` | Fulfill lines, deducting stock |
| `POST` | `pallet/v1/stock-transfers` | Create a transfer with its lines |
| `GET` | `pallet/v1/stock-transfers` | List transfers |
| `GET` | `pallet/v1/stock-transfers/{id}` | Retrieve a transfer |
| `DELETE` | `pallet/v1/stock-transfers/{id}` | Delete a transfer |
| `POST` | `pallet/v1/stock-transfers/{id}/approve` | Approve a pending transfer |
| `POST` | `pallet/v1/stock-transfers/{id}/ship` | Ship, deducting from the source |
| `POST` | `pallet/v1/stock-transfers/{id}/receive` | Receive, crediting the destination |
| `POST` | `pallet/v1/stock-transfers/{id}/cancel` | Cancel, restoring shipped stock |
| `GET` | `pallet/v1/batches` | List batches |
| `GET` | `pallet/v1/batches/{id}` | Retrieve a batch |
| `GET` | `pallet/v1/audits` | List audit entries |
| `GET` | `pallet/v1/audits/{id}` | Retrieve an audit entry |

### What the API deliberately will not do

Four resources refuse writes that would let a caller describe something that never happened:

* **Stock levels** have no create, update or delete. Stock is a consequence of receipts, fulfilments, transfers and adjustments — move it through the operation that caused the movement.
* **Adjustments** cannot be updated or deleted. An adjustment records what happened; correcting one means making another, which is why the history shows both.
* **Batches** are read-only. A batch is produced by receiving stock.
* **Audit entries** are read-only. They are written by the system as operations happen.

Transfers follow the same principle in a different form: there is no settable `status`. Use `approve`, `ship`, `receive` and `cancel`, so the record and the stock move together.

### Checking availability

The question an integrator usually has is whether a quantity can be committed, so the endpoint answers that directly rather than returning figures to compare:

```bash
curl "https://api.fleetbase.io/pallet/v1/inventory/availability?product=product_a1b2c3&quantity=10" \
  -u "$FLEETBASE_API_KEY:"
```

```json
{
    "object": "inventory_availability",
    "product": "product_a1b2c3",
    "sku": "WIDGET-001",
    "warehouse": null,
    "requested_quantity": 10,
    "available": true,
    "available_quantity": 14,
    "reserved_quantity": 6,
    "quantity": 20,
    "shortage_quantity": 0,
    "out_of_stock": false,
    "by_warehouse": [
        {
            "warehouse": "warehouse_x1y2z3",
            "warehouse_name": "Singapore Distribution Center",
            "available_quantity": 14,
            "reserved_quantity": 6,
            "quantity": 20
        }
    ]
}
```

Totals are aggregated across every warehouse holding the product and broken down per warehouse so a caller can choose where to source from. Pass `warehouse=<public id>` to narrow to one, or `sku=` in place of `product=` when integrating against your own catalogue.

### Receiving against a purchase order

An order and its lines are created together, then received by line:

```bash
curl -X POST https://api.fleetbase.io/pallet/v1/purchase-orders \
  -u "$FLEETBASE_API_KEY:" \
  -H "Content-Type: application/json" \
  -d '{
    "supplier": "supplier_a1b2c3",
    "warehouse": "warehouse_x1y2z3",
    "items": [{ "product": "product_a1b2c3", "quantity": 20, "unit_price": 5.00 }]
  }'
```

```bash
curl -X POST https://api.fleetbase.io/pallet/v1/purchase-orders/purchase_order_d4e5f6/receive \
  -u "$FLEETBASE_API_KEY:" \
  -H "Content-Type: application/json" \
  -d '{ "items": [{ "id": "poi_g7h8i9", "quantity_received": 12 }] }'
```

A receipt is capped at the line's outstanding quantity, so an over-receipt takes what is left rather than being rejected. The order reports `partial` until every line is complete. Fulfilling a sales order works the same way, except that insufficient stock rejects the whole request and leaves inventory untouched.

## Storefront Inventory Integration

Pallet is the canonical inventory authority for Storefront products. Storefront products and variants should be linked to Pallet products and variants by durable UUID fields, not by SKU alone:

* `pallet_products.storefront_product_uuid`
* `pallet_product_variants.storefront_variant_uuid`

SKU and barcode matching can be used for assisted lookup, but checkout enforcement should use the explicit Storefront link fields.

### Internal API Contract

All Storefront integration endpoints are scoped to the authenticated company under the protected internal API prefix:

```text
pallet/int/v1/storefront/inventory/resolve
pallet/int/v1/storefront/inventory/availability
pallet/int/v1/storefront/inventory/availability-batch
pallet/int/v1/storefront/inventory/link
pallet/int/v1/storefront/inventory/unlink
pallet/int/v1/storefront/inventory/reserve
pallet/int/v1/storefront/inventory/reserve-batch
pallet/int/v1/storefront/inventory/reservations/context
pallet/int/v1/storefront/inventory/reservations/{id}/release
pallet/int/v1/storefront/inventory/reservations/{id}/commit
pallet/int/v1/storefront/inventory/reservations/release-batch
pallet/int/v1/storefront/inventory/reservations/commit-batch
pallet/int/v1/storefront/inventory/reservations/release-context
pallet/int/v1/storefront/inventory/reservations/commit-context
```

Storefront should call `availability` or `availability-batch` when cart quantities change, `reserve` or `reserve-batch` when checkout starts, `release` when a checkout expires or is cancelled, and `commit` when an order is captured or fulfilled. Batch and context release/commit endpoints are available for checkout lifecycles that store reservation UUIDs directly or only retain a Storefront checkout/cart/order context. Context release can release expired active reservations so abandoned checkout stock is returned; context commit only uses non-expired active reservations. Availability responses include a stable `inventory_summary` object with total, available, reserved, out-of-stock, low-stock, reorder, Pallet UUID, and Storefront UUID fields.

Checkout reservations can include `storefront_checkout_uuid`, `storefront_cart_uuid`, `storefront_order_uuid`, `storefront_line_uuid`, and `storefront_reservation_key`. The reservation key is idempotent: retrying the same reservation key with the same quantity returns the existing active reservation, and retrying it with a different quantity replaces the reservation with one for the new quantity, releasing the previously held stock first. Either way a key holds at most one active reservation, so a retried or edited checkout line can never double-reserve stock.

Product links can be created one product at a time:

```json
{
    "pallet_product_uuid": "product_...",
    "storefront_product_uuid": "..."
}
```

Variant links can be supplied individually with `pallet_variant_uuid` and `storefront_variant_uuid`, or in a batch:

```json
{
    "pallet_product_uuid": "product_...",
    "storefront_product_uuid": "...",
    "variants": [
        {
            "pallet_variant_uuid": "variant_...",
            "storefront_variant_uuid": "..."
        }
    ]
}
```

## Usage

### Backend

🧹 Keep a modern codebase with **PHP CS Fixer**:
```bash
composer lint
composer lint:fix
```

⚗️ Run static analysis using **PHPStan**:
```bash
composer test:types
```

✅ Run unit tests using **PEST**
```bash
composer test:unit
```

🚀 Run the entire test suite:
```bash
composer test
```

### Frontend

🧹 Keep a modern codebase with **ESLint**:
```bash
pnpm lint
```

✅ Run unit tests using **Ember/QUnit**
```bash
pnpm test
pnpm test:ember
pnpm test:ember-compatibility
```

🚀 Start the Ember Addon/Engine
```bash
pnpm start
```

🔨 Build the Ember Addon/Engine
```bash
pnpm build
```

## Contributing
See the Contributing Guide for details on how to contribute to this project.

## License
This project is licensed under the AGPL-3.0-or-later License.
