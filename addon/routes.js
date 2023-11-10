import buildRoutes from 'ember-engines/routes';

export default buildRoutes(function () {
    this.route('home', { path: '/' });
    this.route('products', function () {
        this.route('index', { path: '/' }, function () {
            this.route('new');
            this.route('details', { path: '/:public_id' });
            this.route('edit', { path: '/edit/:public_id' });
        });
    });
    this.route('inventory', function () {});
    this.route('warehouses', function () {});
    this.route('suppliers', function () {});
    this.route('sales-orders', function () {});
    this.route('purchase-orders', function () {});
    this.route('batch', function () {});
    this.route('audits', function () {});
    this.route('reports', function () {});
});
