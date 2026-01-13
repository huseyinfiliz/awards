import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Category from '../../common/models/Category';
import Award from '../../common/models/Award';
import NomineeCard from './NomineeCard';
import OtherCard from './OtherCard';

export default class CategoryCard extends Component {
  view() {
    const category = this.attrs.category as Category;
    const award = this.attrs.award as Award;
    const nominees = category.nominees() || [];

    return (
      <div className="CategoryCard" id={`category-${category.id()}`}>
        <div className="CategoryCard-header">
            <h2>{category.name()}</h2>
            <p>{category.description()}</p>
        </div>

        <div className="CategoryCard-grid">
            {nominees.map(nominee => (
                <NomineeCard nominee={nominee} category={category} award={award} />
            ))}

            {category.allowOther() && award.isVotingOpen() && app.session.user && (
                <OtherCard category={category} />
            )}
        </div>

        {nominees.length === 0 && !category.allowOther() && (
             <div className="EmptyState">
                <p>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_nominees')}</p>
             </div>
        )}
      </div>
    );
  }
}
