import Model from 'flarum/common/Model';
import Category from './Category';

export default class Award extends Model {
  name = Model.attribute<string>('name');
  slug = Model.attribute<string>('slug');
  year = Model.attribute<number>('year');
  description = Model.attribute<string>('description');
  startsAt = Model.attribute('startsAt', Model.transformDate);
  endsAt = Model.attribute('endsAt', Model.transformDate);
  status = Model.attribute<string>('status');
  showLiveVotes = Model.attribute<boolean>('showLiveVotes');
  imageUrl = Model.attribute<string>('imageUrl');
  createdAt = Model.attribute('createdAt', Model.transformDate);
  updatedAt = Model.attribute('updatedAt', Model.transformDate);

  categories = Model.hasMany<Category>('categories');

  categoryCount = Model.attribute<number>('categoryCount');
  nomineeCount = Model.attribute<number>('nomineeCount');
  voteCount = Model.attribute<number>('voteCount');
  
  canViewResults = Model.attribute<boolean>('canViewResults');

  isDraft(): boolean {
    return this.status() === 'draft';
  }

  isActive(): boolean {
    return this.status() === 'active';
  }

  hasEnded(): boolean {
    return this.status() === 'ended';
  }

  isPublished(): boolean {
    return this.status() === 'published';
  }

  isVotingOpen(): boolean {
    const now = new Date();
    const startsAt = this.startsAt();
    const endsAt = this.endsAt();
    return this.isActive() && startsAt && endsAt && now >= startsAt && now <= endsAt;
  }

  canShowVotes(): boolean {
    return this.isPublished() || (this.isActive() && this.showLiveVotes());
  }
}
