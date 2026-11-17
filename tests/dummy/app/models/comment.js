import Model, { attr } from '@ember-data/model';

// Stand-in for the console host's `comment` model, which pallet's models relate to.
export default class CommentModel extends Model {
    @attr('string') public_id;
}
