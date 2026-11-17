import Model, { attr } from '@ember-data/model';

// Stand-in for the console host's `custom-field-value` model, which pallet's models relate to.
export default class CustomFieldValueModel extends Model {
    @attr('string') public_id;
}
