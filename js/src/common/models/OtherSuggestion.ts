import Model from 'flarum/common/Model';
import Category from './Category';
import Nominee from './Nominee';

export default class OtherSuggestion extends Model {
  name = Model.attribute<string>('name');
  status = Model.attribute<string>('status');
  createdAt = Model.attribute('createdAt', Model.transformDate);
  updatedAt = Model.attribute('updatedAt', Model.transformDate);

  category = Model.hasOne<Category>('category');
  user = Model.hasOne('user');
  mergedToNominee = Model.hasOne<Nominee>('mergedToNominee');

  isPending(): boolean {
    return this.status() === 'pending';
  }

  isApproved(): boolean {
    return this.status() === 'approved';
  }

  isRejected(): boolean {
    return this.status() === 'rejected';
  }

  isMerged(): boolean {
    return this.status() === 'merged';
  }
}
