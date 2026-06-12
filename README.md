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
├── assets
├── translations
├── config
├── node_modules
├── server
│ ├── config
│ ├── data
│ ├── migrations
│ ├── resources
│ ├── src
│ ├── tests
│ └── vendor
├── tests
├── testem.js
├── index.js
├── package.json
├── phpstan.neon.dist
├── phpunit.xml.dist
├── pnpm-lock.yaml
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
