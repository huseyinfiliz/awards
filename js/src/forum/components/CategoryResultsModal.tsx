import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Category from '../../common/models/Category';
import Award from '../../common/models/Award';
import rankNominees from '../utils/rankNominees';

interface CategoryResultsModalAttrs extends IInternalModalAttrs {
  category: Category;
  award: Award;
}

export default class CategoryResultsModal extends Modal {
  attrs!: CategoryResultsModalAttrs;
  className() {
    return 'HFAwardsModal CategoryResultsModal';
  }

  title() {
    const category = this.attrs.category as Category;
    return (
      <span>
        {category.name()} - {app.translator.trans('huseyinfiliz-awards.forum.results.full_results')}
      </span>
    );
  }

  content() {
    const category = this.attrs.category as Category;
    const award = this.attrs.award as Award;
    const ranked = rankNominees(category.nominees() || []);
    const totalVotes = ranked.reduce((sum, entry) => sum + entry.votes, 0);

    const canShowVotes = award.canShowVotes();

    return (
      <div className="Modal-body">
        {canShowVotes && totalVotes === 0 ? (
          <p className="CategoryResultsModal-noVotes helpText">{app.translator.trans('huseyinfiliz-awards.forum.results.no_votes')}</p>
        ) : null}
        <div className="CategoryResultsModal-list">
          {ranked.map(({ nominee, votes: voteCount, rank }) => {
            const percentage = totalVotes > 0 ? Math.round((voteCount / totalVotes) * 100) : 0;
            const isWinner = rank === 1;
            const isRunnerUp = rank === 2;
            const isThird = rank === 3;

            return (
              <div className={`CategoryResultsModal-item ${isWinner ? 'CategoryResultsModal-item--winner' : ''}`} key={nominee.id()}>
                <div className="CategoryResultsModal-item-rank">
                  {isWinner ? (
                    <span className="CategoryResultsModal-medal CategoryResultsModal-medal--gold">
                      <i className="fas fa-medal" />
                    </span>
                  ) : isRunnerUp ? (
                    <span className="CategoryResultsModal-medal CategoryResultsModal-medal--silver">
                      <i className="fas fa-medal" />
                    </span>
                  ) : isThird ? (
                    <span className="CategoryResultsModal-medal CategoryResultsModal-medal--bronze">
                      <i className="fas fa-medal" />
                    </span>
                  ) : (
                    <span className="CategoryResultsModal-rankNumber">{rank ?? '—'}</span>
                  )}
                </div>

                <div className="CategoryResultsModal-item-nominee">
                  {nominee.imageUrl() ? (
                    <img className="CategoryResultsModal-item-image" src={nominee.imageUrl()} alt={nominee.name() as string} />
                  ) : (
                    <div className="CategoryResultsModal-item-placeholder">
                      <i className="fas fa-user" />
                    </div>
                  )}
                  <span className="CategoryResultsModal-item-name">{nominee.name()}</span>
                </div>

                {canShowVotes ? (
                  <div className="CategoryResultsModal-item-stats">
                    <span className="CategoryResultsModal-item-votes">{voteCount}</span>
                    <div className="CategoryResultsModal-item-bar">
                      <div className="CategoryResultsModal-item-barFill" style={{ width: `${percentage}%` }} />
                    </div>
                    <span className="CategoryResultsModal-item-percentage">{percentage}%</span>
                  </div>
                ) : null}
              </div>
            );
          })}
        </div>
      </div>
    );
  }
}
