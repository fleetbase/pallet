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
        this.route('locations', function () {
            this.route('index', { path: '/' }, function () {
                this.route('new');
                this.route('details', { path: '/:public_id' }, function () {
                    this.route('index', { path: '/' });
                });
                this.route('edit', { path: '/edit/:public_id' });
            });
        });
        this.route('zones', function () {
            this.route('index', { path: '/' }, function () {
                this.route('new');
                this.route('details', { path: '/:public_id' }, function () {
                    this.route('index', { path: '/' });
                });
                this.route('edit', { path: '/edit/:public_id' });
            });
        });
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
                this.route('edit', { path: '/edit/:public_id' });
            });
            // A sales order is a document too, and moves out of `index` for the same
            // reason the purchase order did — see the note below. The url is unchanged.
            this.route('details', { path: '/:public_id' }, function () {
                this.route('index', { path: '/' });
            });
        });
        this.route('purchase-orders', function () {
            this.route('index', { path: '/' }, function () {
                this.route('new');
                this.route('edit', { path: '/edit/:public_id' });
            });
            /*
             * A purchase order is a document, not a record preview, so its detail view is
             * full-width rather than a side panel (DESIGN_DECISIONS §1.2, accepted).
             *
             * That forces it out of `index`: the index template renders the table and then
             * {{outlet}}, so a child route renders BELOW the whole list. An overlay got away
             * with it by floating; a full-width view would have sat under the table.
             *
             * As a sibling at the same path the URL is unchanged — `index` is path '/', so
             * /purchase-orders/:public_id resolves here either way, and Ember matches the
             * static `new` segment before this dynamic one.
             */
            this.route('details', { path: '/:public_id' }, function () {
                this.route('index', { path: '/' });
            });
        });
    });
    this.route('operations', function () {
        /*
         * Same shape as the order documents: `index` holds the list at path '/', and
         * `details` is a SIBLING rather than a child, because the list template ends
         * with the table and a child route would render underneath the whole thing.
         */
        this.route('transfers', function () {
            this.route('index', { path: '/' });
            this.route('details', { path: '/:public_id' }, function () {
                this.route('index', { path: '/' });
            });
        });
        this.route('cycle-counts', function () {
            this.route('index', { path: '/' });
            this.route('details', { path: '/:public_id' }, function () {
                this.route('index', { path: '/' });
            });
            /*
             * The count sheet is a sibling of `details`, not a child of it: it is its own
             * full-width working screen, and as a child it would render inside the
             * document's header and panel wrapper.
             */
            this.route('count', { path: '/:public_id/count' });
        });
        this.route('pick-lists', function () {
            this.route('index', { path: '/' });
            this.route('details', { path: '/:public_id' }, function () {
                this.route('index', { path: '/' });
            });
            // The pick screen is a sibling, like the count sheet: its own working
            // screen, not something rendered inside the document's panels.
            this.route('pick', { path: '/:public_id/pick' });
        });
        this.route('waves', function () {
            this.route('index', { path: '/' });
            this.route('details', { path: '/:public_id' }, function () {
                this.route('index', { path: '/' });
            });
        });
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
