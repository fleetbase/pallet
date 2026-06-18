import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class ApplicationController extends Controller {
    @service fetch;

    get navigationItems() {
        return [
            {
                label: 'Dashboard',
                description: 'Pallet dashboard and warehouse operations overview.',
                icon: 'home',
                route: 'console.pallet.home',
                keywords: ['overview', 'warehouse dashboard', 'inventory dashboard'],
            },
            {
                label: 'Catalog',
                description: 'Products, variants, and supplier records.',
                icon: 'boxes-stacked',
                children: [
                    {
                        label: 'Products',
                        description: 'Manage product SKUs, variants, and catalog details.',
                        icon: 'boxes-stacked',
                        route: 'console.pallet.catalog.products',
                        keywords: ['sku', 'variants', 'items', 'catalog'],
                    },
                    {
                        label: 'Suppliers',
                        description: 'Manage suppliers used for purchasing and replenishment.',
                        icon: 'user-tie',
                        route: 'console.pallet.catalog.suppliers',
                        keywords: ['vendors', 'procurement', 'purchasing'],
                    },
                ],
            },
            {
                label: 'Facilities',
                description: 'Warehouses, zones, and bin locations.',
                icon: 'warehouse',
                children: [
                    {
                        label: 'Warehouses',
                        description: 'Manage warehouse facilities and operating details.',
                        icon: 'warehouse',
                        route: 'console.pallet.facilities.warehouses',
                        keywords: ['facilities', 'storage', 'warehouse'],
                    },
                    {
                        label: 'Locations',
                        description: 'Manage bin locations inside warehouses.',
                        icon: 'sitemap',
                        route: 'console.pallet.facilities.locations',
                        keywords: ['bins', 'bin locations', 'storage locations'],
                    },
                    {
                        label: 'Zones',
                        description: 'Manage warehouse zones and storage areas.',
                        icon: 'border-all',
                        route: 'console.pallet.facilities.zones',
                        keywords: ['warehouse zones', 'areas', 'storage zones'],
                    },
                ],
            },
            {
                label: 'Inventory',
                description: 'Stock, batches, adjustments, and exception views.',
                icon: 'barcode',
                children: [
                    {
                        label: 'Inventory',
                        description: 'Review current stock by product, variant, and warehouse.',
                        icon: 'barcode',
                        route: 'console.pallet.inventory.index',
                        keywords: ['stock', 'inventory levels', 'available quantity'],
                    },
                    {
                        label: 'Batches',
                        description: 'Review batch and lot-controlled stock.',
                        icon: 'layer-group',
                        route: 'console.pallet.inventory.batches',
                        keywords: ['lots', 'lot number', 'batch number'],
                    },
                    {
                        label: 'Stock Adjustments',
                        description: 'Review manual stock adjustments and corrections.',
                        icon: 'sliders-h',
                        route: 'console.pallet.inventory.adjustments',
                        keywords: ['adjustments', 'corrections', 'inventory changes'],
                    },
                    {
                        label: 'Low Stock',
                        description: 'Find items that are below reorder thresholds.',
                        icon: 'exclamation-triangle',
                        route: 'console.pallet.inventory.low-stock',
                        keywords: ['reorder', 'low inventory', 'shortage'],
                    },
                    {
                        label: 'Expired Stock',
                        description: 'Find expired and aging inventory.',
                        icon: 'calendar-times',
                        route: 'console.pallet.inventory.expired-stock',
                        keywords: ['expiry', 'expiration', 'perishable'],
                    },
                ],
            },
            {
                label: 'Orders',
                description: 'Purchase orders and sales orders.',
                icon: 'file-invoice-dollar',
                children: [
                    {
                        label: 'Purchase Orders',
                        description: 'Manage inbound purchase orders and receiving.',
                        icon: 'file-invoice-dollar',
                        route: 'console.pallet.orders.purchase-orders',
                        keywords: ['po', 'receiving', 'procurement', 'inbound'],
                    },
                    {
                        label: 'Sales Orders',
                        description: 'Manage outbound sales orders and fulfillment.',
                        icon: 'cash-register',
                        route: 'console.pallet.orders.sales-orders',
                        keywords: ['so', 'fulfillment', 'outbound', 'customer orders'],
                    },
                ],
            },
            {
                label: 'Operations',
                description: 'Transfers, cycle counts, pick lists, waves, and reservations.',
                icon: 'exchange-alt',
                children: [
                    {
                        label: 'Transfers',
                        description: 'Move stock between warehouses and locations.',
                        icon: 'exchange-alt',
                        route: 'console.pallet.operations.transfers',
                        keywords: ['stock transfers', 'warehouse transfers'],
                    },
                    {
                        label: 'Cycle Counts',
                        description: 'Run and review inventory count workflows.',
                        icon: 'clipboard-list',
                        route: 'console.pallet.operations.cycle-counts',
                        keywords: ['counts', 'stock count', 'inventory audit'],
                    },
                    {
                        label: 'Pick Lists',
                        description: 'Manage picking work for outbound orders.',
                        icon: 'list',
                        route: 'console.pallet.operations.pick-lists',
                        keywords: ['picking', 'pick tickets', 'fulfillment'],
                    },
                    {
                        label: 'Waves',
                        description: 'Group fulfillment work into picking waves.',
                        icon: 'water',
                        route: 'console.pallet.operations.waves',
                        keywords: ['wave picking', 'batch picking'],
                    },
                    {
                        label: 'Reservations',
                        description: 'Review reserved inventory and allocation state.',
                        icon: 'lock',
                        route: 'console.pallet.operations.reservations',
                        keywords: ['allocated stock', 'reserved stock', 'holds'],
                    },
                ],
            },
            {
                label: 'Analytics',
                description: 'Pallet reporting and operational audit trail.',
                icon: 'chart-line',
                children: [
                    {
                        label: 'Reports',
                        description: 'Build and review Pallet reports.',
                        icon: 'chart-line',
                        route: 'console.pallet.analytics.reports',
                        keywords: ['reporting', 'analytics', 'warehouse reports'],
                    },
                    {
                        label: 'Audits',
                        description: 'Review warehouse and inventory audit events.',
                        icon: 'magnifying-glass',
                        route: 'console.pallet.analytics.audits',
                        keywords: ['audit trail', 'activity', 'events'],
                    },
                ],
            },
        ];
    }

    @action
    async searchNavigation({ query, limit = 12 }) {
        const trimmedQuery = query?.trim();

        if (!trimmedQuery) {
            return [];
        }

        try {
            const response = await this.fetch.get(
                'search',
                {
                    query: trimmedQuery,
                    limit,
                },
                {
                    namespace: 'pallet/int/v1',
                }
            );

            return response.results ?? [];
        } catch (_) {
            return [];
        }
    }
}
