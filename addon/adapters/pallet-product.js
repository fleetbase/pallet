import PalletAdapter from './pallet';
import { dasherize } from '@ember/string';
import { pluralize } from 'ember-inflector';

export default class PalletProductAdapter extends PalletAdapter {
    pathForType(modelName) {
        let dasherized = dasherize(modelName.replace('pallet-', ''));
        let pluralized = pluralize(dasherized);

        return pluralized;
    }
}
