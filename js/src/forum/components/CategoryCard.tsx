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
    const nomineeCount = nominees.length;

    return (
      <div className="CategoryCard" id={`category-${category.id()}`}>
        <div className="CategoryCard-header">
          <div className="CategoryCard-headerMain">
            <h2>{category.name()}</h2>
            {category.description() ? <p>{category.description()}</p> : null}
          </div>
          {nomineeCount > 0 ? (
            <div className="CategoryCard-badge">
              {app.translator.trans('huseyinfiliz-awards.forum.category.nominees_count', { count: nomineeCount })}
            </div>
          ) : null}
        </div>

        <div className="CategoryCard-grid">
          {nominees.map((nominee) => (
            <NomineeCard nominee={nominee} category={category} award={award} />
          ))}

          {category.allowOther() && award.isVotingOpen() && app.session.user ? (
            <OtherCard category={category} />
          ) : null}
        </div>

        {nominees.length === 0 && !category.allowOther() ? (
          <div className="EmptyState">
            <p>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_nominees')}</p>
          </div>
        ) : null}
      </div>
    );
  }
}
