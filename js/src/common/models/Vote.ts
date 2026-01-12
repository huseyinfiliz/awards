import Model from 'flarum/common/Model';
import Nominee from './Nominee';
import Category from './Category';

export default class Vote extends Model {
  createdAt = Model.attribute('createdAt', Model.transformDate);

  nominee = Model.hasOne<Nominee>('nominee');
  category = Model.hasOne<Category>('category');
  user = Model.hasOne('user');
}
