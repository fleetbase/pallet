import Model, { attr } from '@ember-data/model';

// Stand-in for the console host's `file` model, which pallet's models relate to.
export default class FileModel extends Model {
    @attr('string') public_id;
}
