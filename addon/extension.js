import { Widget, ExtensionComponent } from '@fleetbase/ember-core/contracts';

export default {
    setupExtension(app, universe) {
        const menuService = universe.getService('universe/menu-service');
        const widgetService = universe.getService('universe/widget-service');

        // Register header navigation
        menuService.registerHeaderMenuItem('Pallet', 'console.pallet', { icon: 'pallet', priority: 1 });

        // Register dashboard and widgets
        this.registerWidgets(widgetService);
    },

    registerWidgets(widgetService) {
        const widgets = [
            new Widget({
                id: 'pallet-inventory-summary',
                name: 'Inventory Summary',
                description: 'Overview of total SKUs, total stock units, and inventory value across all warehouses',
                icon: 'boxes-stacked',
                component: new ExtensionComponent('@fleetbase/pallet-engine', 'widget/inventory-summary'),
                grid_options: { w: 12, h: 4, minW: 8, minH: 3 },
                options: { title: 'Inventory Summary' },
                default: true,
            }),

            new Widget({
                id: 'pallet-low-stock',
                name: 'Low Stock Alerts',
                description: 'Products that have fallen at or below their minimum stock threshold',
                icon: 'triangle-exclamation',
                component: new ExtensionComponent('@fleetbase/pallet-engine', 'widget/low-stock'),
                grid_options: { w: 6, h: 8, minW: 4, minH: 6 },
                options: { title: 'Low Stock Alerts' },
                default: true,
            }),

            new Widget({
                id: 'pallet-po-status',
                name: 'Purchase Order Status',
                description: 'Breakdown of purchase orders by status - pending, partially received, and fully received',
                icon: 'truck-ramp-box',
                component: new ExtensionComponent('@fleetbase/pallet-engine', 'widget/po-status'),
                grid_options: { w: 6, h: 8, minW: 4, minH: 6 },
                options: { title: 'Purchase Order Status' },
                default: true,
            }),

            new Widget({
                id: 'pallet-so-status',
                name: 'Sales Order Status',
                description: 'Breakdown of sales orders by status - pending, partially fulfilled, and fully fulfilled',
                icon: 'file-invoice',
                component: new ExtensionComponent('@fleetbase/pallet-engine', 'widget/so-status'),
                grid_options: { w: 6, h: 8, minW: 4, minH: 6 },
                options: { title: 'Sales Order Status' },
                default: true,
            }),

            new Widget({
                id: 'pallet-recent-activity',
                name: 'Recent Activity',
                description: 'Latest operational audit trail events - stock adjustments, receipts, fulfillments, and cycle counts',
                icon: 'clock-rotate-left',
                component: new ExtensionComponent('@fleetbase/pallet-engine', 'widget/recent-activity'),
                grid_options: { w: 6, h: 8, minW: 4, minH: 6 },
                options: { title: 'Recent Activity' },
                default: true,
            }),

            new Widget({
                id: 'pallet-stock-value',
                name: 'Stock Value by Warehouse',
                description: 'Total inventory value broken down per warehouse',
                icon: 'chart-bar',
                component: new ExtensionComponent('@fleetbase/pallet-engine', 'widget/stock-value'),
                grid_options: { w: 8, h: 7, minW: 6, minH: 5 },
                options: { title: 'Stock Value by Warehouse' },
            }),

            new Widget({
                id: 'pallet-expiring-stock',
                name: 'Expiring Stock',
                description: 'Inventory batches that are expiring within the next 30 days',
                icon: 'calendar-xmark',
                component: new ExtensionComponent('@fleetbase/pallet-engine', 'widget/expiring-stock'),
                grid_options: { w: 6, h: 7, minW: 4, minH: 5 },
                options: { title: 'Expiring Stock' },
            }),

            new Widget({
                id: 'pallet-top-products',
                name: 'Top Products by Movement',
                description: 'Most frequently moved products based on stock transactions',
                icon: 'arrow-trend-up',
                component: new ExtensionComponent('@fleetbase/pallet-engine', 'widget/top-products'),
                grid_options: { w: 6, h: 7, minW: 4, minH: 5 },
                options: { title: 'Top Products by Movement' },
            }),
        ];

        widgetService.registerDashboard('pallet');
        widgetService.registerWidgets('pallet', widgets);
    },
};
