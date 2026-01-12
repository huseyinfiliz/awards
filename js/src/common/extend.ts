import Extend from 'flarum/common/extenders';
import Award from './models/Award';
import Category from './models/Category';
import Nominee from './models/Nominee';
import Vote from './models/Vote';
import OtherSuggestion from './models/OtherSuggestion';

export default [
  new Extend.Store()
    .add('awards', Award)
    .add('award-categories', Category)
    .add('award-nominees', Nominee)
    .add('award-votes', Vote)
    .add('award-other-suggestions', OtherSuggestion),
];
