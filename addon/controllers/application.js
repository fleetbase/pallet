import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class ApplicationController extends Controller {
    @service fetch;
    @service intl;

    get navigationItems() {
        return [
            {
                label: this.intl.t('navigation.dashboard.label'),
                description: this.intl.t('navigation.dashboard.description'),
                icon: 'home',
                route: 'console.pallet.home',
                keywords: ['overview', 'warehouse dashboard', 'inventory dashboard'],
            },
            {
                label: this.intl.t('navigation.catalog.label'),
                description: this.intl.t('navigation.catalog.description'),
                icon: 'boxes-stacked',
                children: [
                    {
                        label: this.intl.t('navigation.products.label'),
                        description: this.intl.t('navigation.products.description'),
                        icon: 'boxes-stacked',
                        route: 'console.pallet.catalog.products',
                        keywords: ['sku', 'variants', 'items', 'catalog'],
                    },
                    {
                        label: this.intl.t('navigation.suppliers.label'),
                        description: this.intl.t('navigation.suppliers.description'),
                        icon: 'user-tie',
                        route: 'console.pallet.catalog.suppliers',
                        keywords: ['vendors', 'procurement', 'purchasing'],
                    },
                ],
            },
            {
                label: this.intl.t('navigation.facilities.label'),
                description: this.intl.t('navigation.facilities.description'),
                icon: 'warehouse',
                children: [
                    {
                        label: this.intl.t('navigation.warehouses.label'),
                        description: this.intl.t('navigation.warehouses.description'),
                        icon: 'warehouse',
                        route: 'console.pallet.facilities.warehouses',
                        keywords: ['facilities', 'storage', 'warehouse'],
                    },
                    {
                        label: this.intl.t('navigation.locations.label'),
                        description: this.intl.t('navigation.locations.description'),
                        icon: 'sitemap',
                        route: 'console.pallet.facilities.locations',
                        keywords: ['bins', 'bin locations', 'storage locations'],
                    },
                    {
                        label: this.intl.t('navigation.zones.label'),
                        description: this.intl.t('navigation.zones.description'),
                        icon: 'border-all',
                        route: 'console.pallet.facilities.zones',
                        keywords: ['warehouse zones', 'areas', 'storage zones'],
                    },
                ],
            },
            {
                label: this.intl.t('navigation.inventory.label'),
                description: this.intl.t('navigation.inventory.description'),
                icon: 'barcode',
                children: [
                    {
                        label: this.intl.t('navigation.inventory-index.label'),
                        description: this.intl.t('navigation.inventory-index.description'),
                        icon: 'barcode',
                        route: 'console.pallet.inventory.index',
                        keywords: ['stock', 'inventory levels', 'available quantity'],
                    },
                    {
                        label: this.intl.t('navigation.batches.label'),
                        description: this.intl.t('navigation.batches.description'),
                        icon: 'layer-group',
                        route: 'console.pallet.inventory.batches',
                        keywords: ['lots', 'lot number', 'batch number'],
                    },
                    {
                        label: this.intl.t('navigation.stock-adjustments.label'),
                        description: this.intl.t('navigation.stock-adjustments.description'),
                        icon: 'sliders-h',
                        route: 'console.pallet.inventory.adjustments',
                        keywords: ['adjustments', 'corrections', 'inventory changes'],
                    },
                    {
                        label: this.intl.t('navigation.low-stock.label'),
                        description: this.intl.t('navigation.low-stock.description'),
                        icon: 'exclamation-triangle',
                        route: 'console.pallet.inventory.low-stock',
                        keywords: ['reorder', 'low inventory', 'shortage'],
                    },
                    {
                        label: this.intl.t('navigation.expired-stock.label'),
                        description: this.intl.t('navigation.expired-stock.description'),
                        icon: 'calendar-times',
                        route: 'console.pallet.inventory.expired-stock',
                        keywords: ['expiry', 'expiration', 'perishable'],
                    },
                ],
            },
            {
                label: this.intl.t('navigation.orders.label'),
                description: this.intl.t('navigation.orders.description'),
                icon: 'file-invoice-dollar',
                children: [
                    {
                        label: this.intl.t('navigation.purchase-orders.label'),
                        description: this.intl.t('navigation.purchase-orders.description'),
                        icon: 'file-invoice-dollar',
                        route: 'console.pallet.orders.purchase-orders',
                        keywords: ['po', 'receiving', 'procurement', 'inbound'],
                    },
                    {
                        label: this.intl.t('navigation.sales-orders.label'),
                        description: this.intl.t('navigation.sales-orders.description'),
                        icon: 'cash-register',
                        route: 'console.pallet.orders.sales-orders',
                        keywords: ['so', 'fulfillment', 'outbound', 'customer orders'],
                    },
                ],
            },
            {
                label: this.intl.t('navigation.operations.label'),
                description: this.intl.t('navigation.operations.description'),
                icon: 'exchange-alt',
                children: [
                    {
                        label: this.intl.t('navigation.transfers.label'),
                        description: this.intl.t('navigation.transfers.description'),
                        icon: 'exchange-alt',
                        route: 'console.pallet.operations.transfers',
                        keywords: ['stock transfers', 'warehouse transfers'],
                    },
                    {
                        label: this.intl.t('navigation.cycle-counts.label'),
                        description: this.intl.t('navigation.cycle-counts.description'),
                        icon: 'clipboard-list',
                        route: 'console.pallet.operations.cycle-counts',
                        keywords: ['counts', 'stock count', 'inventory audit'],
                    },
                    {
                        label: this.intl.t('navigation.pick-lists.label'),
                        description: this.intl.t('navigation.pick-lists.description'),
                        icon: 'list',
                        route: 'console.pallet.operations.pick-lists',
                        keywords: ['picking', 'pick tickets', 'fulfillment'],
                    },
                    {
                        label: this.intl.t('navigation.waves.label'),
                        description: this.intl.t('navigation.waves.description'),
                        icon: 'water',
                        route: 'console.pallet.operations.waves',
                        keywords: ['wave picking', 'batch picking'],
                    },
                    {
                        label: this.intl.t('navigation.reservations.label'),
                        description: this.intl.t('navigation.reservations.description'),
                        icon: 'lock',
                        route: 'console.pallet.operations.reservations',
                        keywords: ['allocated stock', 'reserved stock', 'holds'],
                    },
                ],
            },
            {
                label: this.intl.t('navigation.analytics.label'),
                description: this.intl.t('navigation.analytics.description'),
                icon: 'chart-line',
                children: [
                    {
                        label: this.intl.t('navigation.reports.label'),
                        description: this.intl.t('navigation.reports.description'),
                        icon: 'chart-line',
                        route: 'console.pallet.analytics.reports',
                        keywords: ['reporting', 'analytics', 'warehouse reports'],
                    },
                    {
                        label: this.intl.t('navigation.audits.label'),
                        description: this.intl.t('navigation.audits.description'),
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
