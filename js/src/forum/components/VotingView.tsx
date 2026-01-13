import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Award from '../../common/models/Award';
import CategoryCard from './CategoryCard';

export default class VotingView extends Component {
  formatEndDate(date: Date | null): string {
    if (!date) return '';

    const now = new Date();
    const diff = date.getTime() - now.getTime();

    if (diff <= 0) {
      return app.translator.trans('huseyinfiliz-awards.forum.voting.ended') as string;
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

    if (days > 0) {
      return app.translator.trans('huseyinfiliz-awards.forum.voting.ends_in_days', { days }) as string;
    }
    if (hours > 0) {
      return app.translator.trans('huseyinfiliz-awards.forum.voting.ends_in_hours', { hours }) as string;
    }
    return app.translator.trans('huseyinfiliz-awards.forum.voting.ends_soon') as string;
  }

  view() {
    const award = this.attrs.award as Award;
    const categories = award.categories() || [];
    const endsAt = award.endsAt();

    return (
      <div className="VotingView">
        <div className="VotingView-status">
          {award.isVotingOpen() ? (
            <span className="TagLabel Label--success">
              {this.formatEndDate(endsAt)}
            </span>
          ) : (
            <span className="TagLabel Label--warning">
              {app.translator.trans('huseyinfiliz-awards.forum.error.voting_closed')}
            </span>
          )}
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
