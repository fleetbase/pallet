import Model, { attr } from '@ember-data/model';

// Stand-in for the console host's `company` model, which pallet's models relate to.
export default class CompanyModel extends Model {
    @attr('string') public_id;
}
