import Model from 'flarum/common/Model';
import Category from './Category';

export default class Nominee extends Model {
  name = Model.attribute<string>('name');
  slug = Model.attribute<string>('slug');
  imageUrl = Model.attribute<string>('imageUrl');
  metadata = Model.attribute<Record<string, any>>('metadata');
  sortOrder = Model.attribute<number>('sortOrder');
  createdAt = Model.attribute('createdAt', Model.transformDate);
  updatedAt = Model.attribute('updatedAt', Model.transformDate);

  category = Model.hasOne<Category>('category');

  voteCount = Model.attribute<number>('voteCount');
  votePercentage = Model.attribute<number>('votePercentage');
  rank = Model.attribute<number>('rank');
  isWinner = Model.attribute<boolean>('isWinner');
  isRunnerUp = Model.attribute<boolean>('isRunnerUp');
  isThirdPlace = Model.attribute<boolean>('isThirdPlace');
}
