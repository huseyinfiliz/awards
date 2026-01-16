import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Category from '../../common/models/Category';
import Nominee from '../../common/models/Nominee';
import Award from '../../common/models/Award';

export default class CategoryResultsModal extends Modal {
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
    const nominees = (category.nominees() || []) as Nominee[];

    // Sort by vote count descending
    const sortedNominees = [...nominees].sort((a, b) => (b.voteCount() || 0) - (a.voteCount() || 0));

    // Calculate total votes for percentages
    const totalVotes = sortedNominees.reduce((sum, n) => sum + (n.voteCount() || 0), 0);

    const canShowVotes = award.canShowVotes();

    return (
      <div className="Modal-body">
        <div className="CategoryResultsModal-list">
          {sortedNominees.map((nominee, index) => {
            const voteCount = nominee.voteCount() || 0;
            const percentage = totalVotes > 0 ? Math.round((voteCount / totalVotes) * 100) : 0;
            const isWinner = index === 0;
            const isRunnerUp = index === 1;
            const isThird = index === 2;

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
                    <span className="CategoryResultsModal-rankNumber">{index + 1}</span>
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
