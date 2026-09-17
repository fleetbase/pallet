import Route from '@ember/routing/route';

export default class CatalogProductsIndexDetailsIndexRoute extends Route {
    /**
     * The record is loaded by the parent details route; without this the
     * overview tab renders with no model.
     */
    model() {
        return this.modelFor('catalog.products.index.details');
    }
}
