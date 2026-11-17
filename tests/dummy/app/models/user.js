import Model, { attr } from '@ember-data/model';

// Stand-in for the console host's `user` model, which pallet's models relate to.
export default class UserModel extends Model {
    @attr('string') public_id;
}
