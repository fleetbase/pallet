import PalletAdapter from './pallet';

export default class PalletProductVariantAdapter extends PalletAdapter {
    pathForType() {
        return 'product-variants';
    }
}
