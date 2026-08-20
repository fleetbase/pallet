import buildRoutes from 'ember-engines/routes';

export default buildRoutes(function () {
    this.route('home', { path: '/' });
    this.route('catalog', function () {
        this.route('products', function () {
            this.route('index', { path: '/' }, function () {
                this.route('new');
                this.route('details', { path: '/:public_id' }, function () {
                    this.route('index', { path: '/' });
                });
                this.route('edit', { path: '/edit/:public_id' });
            });
        });
        this.route('suppliers', function () {
            this.route('index', { path: '/' }, function () {
                this.route('new');
                this.route('details', { path: '/:public_id' }, function () {
                    this.route('index', { path: '/' });
                });
                this.route('edit', { path: '/edit/:public_id' });
            });
        });
    });
    this.route('facilities', function () {
        this.route('warehouses', function () {
            this.route('index', { path: '/' }, function () {
                this.route('new');
                this.route('details', { path: '/:public_id' }, function () {
                    this.route('index', { path: '/' });
                });
                this.route('edit', { path: '/edit/:public_id' });
            });
        });
        this.route('locations');
        this.route('zones');
    });
    this.route('inventory', function () {
        this.route('low-stock');
        this.route('expired-stock');
        this.route('batches');
        this.route('adjustments');
        this.route('index', { path: '/' }, function () {
            this.route('new');
            this.route('new-stock-adjustment');
            this.route('details', { path: '/:public_id' }, function () {
                this.route('index', { path: '/' });
            });
            this.route('edit', { path: '/edit/:public_id' });
        });
    });
    this.route('orders', function () {
        this.route('sales-orders', function () {
            this.route('index', { path: '/' }, function () {
                this.route('new');
                this.route('details', { path: '/:public_id' }, function () {
                    this.route('index', { path: '/' });
                });
                this.route('edit', { path: '/edit/:public_id' });
            });
        });
        this.route('purchase-orders', function () {
            this.route('index', { path: '/' }, function () {
                this.route('new');
                this.route('details', { path: '/:public_id' }, function () {
                    this.route('index', { path: '/' });
                });
                this.route('edit', { path: '/edit/:public_id' });
            });
        });
    });
    this.route('operations', function () {
        this.route('transfers');
        this.route('cycle-counts');
        this.route('pick-lists');
        this.route('waves');
        this.route('reservations');
    });
    this.route('analytics', function () {
        this.route('audits', function () {
            this.route('index', { path: '/' }, function () {
                this.route('details', { path: '/:public_id' });
            });
        });
        this.route('reports', function () {
            this.route('index', { path: '/' }, function () {
                this.route('new');
                this.route('details', { path: '/:public_id' }, function () {
                    this.route('index', { path: '/' });
                    this.route('result');
                });
                this.route('edit', { path: '/edit/:public_id' });
            });
        });
    });
});
