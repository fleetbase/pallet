import Model, { attr } from '@ember-data/model';

// Stand-in for the console host's `transaction` model, which pallet's models relate to.
export default class TransactionModel extends Model {
    @attr('string') public_id;
}
