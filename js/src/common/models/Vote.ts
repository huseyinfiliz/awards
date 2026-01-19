import Model from 'flarum/common/Model';
import Nominee from './Nominee';
import Category from './Category';

export default class Vote extends Model {
  createdAt = Model.attribute('createdAt', Model.transformDate);

  // Direct attribute access for IDs (for when relationships aren't loaded)
  nomineeId = Model.attribute<number>('nomineeId');
  categoryId = Model.attribute<number>('categoryId');

  // Relationship accessors
  nominee = Model.hasOne<Nominee>('nominee');
  category = Model.hasOne<Category>('category');
  user = Model.hasOne('user');
}
