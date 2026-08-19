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
