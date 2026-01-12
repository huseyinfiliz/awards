import Model from 'flarum/common/Model';
import Award from './Award';
import Nominee from './Nominee';
import Vote from './Vote';
import OtherSuggestion from './OtherSuggestion';

export default class Category extends Model {
  name = Model.attribute<string>('name');
  slug = Model.attribute<string>('slug');
  description = Model.attribute<string>('description');
  sortOrder = Model.attribute<number>('sortOrder');
  allowOther = Model.attribute<boolean>('allowOther');
  createdAt = Model.attribute('createdAt', Model.transformDate);
  updatedAt = Model.attribute('updatedAt', Model.transformDate);

  award = Model.hasOne<Award>('award');
  nominees = Model.hasMany<Nominee>('nominees');
  votes = Model.hasMany<Vote>('votes');
  otherSuggestions = Model.hasMany<OtherSuggestion>('otherSuggestions');

  nomineeCount = Model.attribute<number>('nomineeCount');
  voteCount = Model.attribute<number>('voteCount');
  userVoteIds = Model.attribute<number[]>('userVoteIds');

  hasUserVoted(): boolean {
    const ids = this.userVoteIds();
    return ids && ids.length > 0;
  }

  getUserVotedNomineeIds(): number[] {
    return this.userVoteIds() || [];
  }
}
