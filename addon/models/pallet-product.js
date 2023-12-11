import EntityModel from '@fleetbase/fleetops-data/models/entity';
import { hasMany } from '@ember-data/model';

export default class PalletProductModel extends EntityModel {
    /** @relationships */
    @hasMany('file') files;
}
