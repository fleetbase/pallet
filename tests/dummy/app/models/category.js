import Model, { attr } from '@ember-data/model';

// Stand-in for the console host's `category` model, which pallet's models relate to.
export default class CategoryModel extends Model {
    @attr('string') public_id;
}
