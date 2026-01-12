import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Award from '../../common/models/Award';
import CategoryCard from './CategoryCard';
import humanTime from 'flarum/common/utils/humanTime';

export default class VotingView extends Component {
  view() {
    const award = this.attrs.award as Award;
    const categories = award.categories() || [];

    return (
      <div className="VotingView">
        <div className="VotingView-header Hero">
          <div className="Hero-content">
            <h1>{award.name()}</h1>
            <p className="lead">{award.description()}</p>
            <div className="VotingView-meta">
              {award.isVotingOpen() ? (
                <span className="TagLabel Label--success">
                  {app.translator.trans('huseyinfiliz-awards.forum.page.voting_ends', { time: humanTime(award.endsAt()) })}
                </span>
              ) : (
                <span className="TagLabel Label--warning">
                  {app.translator.trans('huseyinfiliz-awards.forum.error.voting_closed')}
                </span>
              )}
            </div>
          </div>
        </div>

        <div className="VotingView-content">
          {categories.length === 0 ? (
            <div className="EmptyState">
              <p>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_categories')}</p>
            </div>
          ) : (
            categories.map((category) => <CategoryCard category={category} award={award} />)
          )}
        </div>
      </div>
    );
  }
}
