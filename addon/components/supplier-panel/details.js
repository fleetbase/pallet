import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';

export default class SupplierPanelDetailsComponent extends Component {
    @tracked isIntegratedVendor = false;
    @tracked supplier;
    constructor() {
        super(...arguments);
        this.supplier = this.args.supplier;
        this.isIntegratedVendor = this.supplier && this.supplier.type === 'integrated-supplier';
    }
}
