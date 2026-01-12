import Component from 'flarum/common/Component';
import Category from '../../common/models/Category';
import Award from '../../common/models/Award';
import Badge from 'flarum/common/components/Badge';

export default class CategoryResultCard extends Component {
  view() {
    const category = this.attrs.category as Category;
    const award = this.attrs.award as Award;
    const nominees = (category.nominees() || [])
        .sort((a, b) => b.voteCount() - a.voteCount());

    const canShowVotes = award.canShowVotes();

    return (
      <div className="CategoryCard CategoryResultCard">
        <div className="CategoryCard-header">
            <h2>{category.name()}</h2>
            <p>{category.description()}</p>
        </div>

        <div className="CategoryCard-grid">
            {nominees.map((nominee, index) => {
                const isWinner = index === 0;
                const isRunnerUp = index === 1;
                const isThird = index === 2;

                return (
                    <div className={`NomineeCard ${isWinner ? 'NomineeCard--winner' : ''}`}>
                        <div className="NomineeCard-image">
                            {nominee.imageUrl() ? (
                                <img src={nominee.imageUrl()} alt={nominee.name()} />
                            ) : (
                                <div className="NomineeCard-placeholder">
                                    <i className="fas fa-user" />
                                </div>
                            )}

                            {isWinner && <div className="NomineeCard-badge NomineeCard-badge--gold"><i className="fas fa-medal" /></div>}
                            {isRunnerUp && <div className="NomineeCard-badge NomineeCard-badge--silver"><i className="fas fa-medal" /></div>}
                            {isThird && <div className="NomineeCard-badge NomineeCard-badge--bronze"><i className="fas fa-medal" /></div>}
                        </div>

                        <div className="NomineeCard-content">
                            <h3 className="NomineeCard-title">{nominee.name()}</h3>

                            {isWinner && (
                                <div className="TagLabel Label--success">
                                    {app.translator.trans('huseyinfiliz-awards.forum.results.winner')}
                                </div>
                            )}
                            {isRunnerUp && (
                                <div className="TagLabel Label--info">
                                    {app.translator.trans('huseyinfiliz-awards.forum.results.runner_up')}
                                </div>
                            )}

                            {canShowVotes && (
                                <div className="NomineeCard-stats">
                                    <span className="NomineeCard-votes">
                                        {app.translator.trans('huseyinfiliz-awards.forum.results.votes', { count: nominee.voteCount() })}
                                    </span>
                                    <span className="NomineeCard-percentage">
                                        {app.translator.trans('huseyinfiliz-awards.forum.results.percentage', { percent: nominee.votePercentage() })}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
      </div>
    );
  }
}
