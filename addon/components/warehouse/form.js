import Component from '@glimmer/component';

export default class WarehouseFormComponent extends Component {
    /**
     * The form only ever offered name, contact details and address, so a warehouse's
     * own code, type and status were unreachable — the details panel showed all three
     * as values the user had no way to set.
     *
     * These mirror the column defaults in the warehouses migration.
     */
    typeOptions = ['standard', 'cold-storage', 'hazmat', 'bonded', 'distribution', 'fulfillment'];

    statusOptions = ['active', 'inactive', 'maintenance'];
}
