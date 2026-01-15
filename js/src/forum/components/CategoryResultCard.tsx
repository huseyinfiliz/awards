import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Category from '../../common/models/Category';
import Award from '../../common/models/Award';
import CategoryResultsModal from './CategoryResultsModal';

export default class CategoryResultCard extends Component {
  view() {
    const category = this.attrs.category as Category;
    const award = this.attrs.award as Award;
    const allNominees = category.nominees() || [];
    // Sort by vote count and show only top 3
    const nominees = [...allNominees]
      .sort((a, b) => (b.voteCount() || 0) - (a.voteCount() || 0))
      .slice(0, 3);

    const canShowVotes = award.canShowVotes();
    const hasMoreNominees = allNominees.length > 3;

    return (
      <div className="CategoryCard CategoryResultCard" id={`category-${category.id()}`}>
        <div className="CategoryCard-header">
          <div className="CategoryCard-headerMain">
            <h2>{category.name()}</h2>
            {category.description() ? <p>{category.description()}</p> : null}
          </div>
          {hasMoreNominees ? (
            <Button
              className="Button Button--text CategoryResultCard-viewAll"
              onclick={() => {
                app.modal.show(CategoryResultsModal, { category, award });
              }}
            >
              {app.translator.trans('huseyinfiliz-awards.forum.results.view_all')}
            </Button>
          ) : null}
        </div>

        <div className="CategoryCard-grid">
          {nominees.map((nominee, index) => {
            const isWinner = index === 0;
            const isRunnerUp = index === 1;
            const isThird = index === 2;

            return (
              <div className={`NomineeCard ${isWinner ? 'NomineeCard--winner' : ''}`} key={nominee.id()}>
                <div className="NomineeCard-image">
                  {nominee.imageUrl() ? (
                    <img src={nominee.imageUrl()} alt={nominee.name() as string} />
                  ) : (
                    <div className="NomineeCard-placeholder">
                      <i className="fas fa-user" />
                    </div>
                  )}

                  {isWinner ? <div className="NomineeCard-badge NomineeCard-badge--gold"><i className="fas fa-medal" /></div> : null}
                  {isRunnerUp ? <div className="NomineeCard-badge NomineeCard-badge--silver"><i className="fas fa-medal" /></div> : null}
                  {isThird ? <div className="NomineeCard-badge NomineeCard-badge--bronze"><i className="fas fa-medal" /></div> : null}
                </div>

                <div className="NomineeCard-content">
                  <h3 className="NomineeCard-title">{nominee.name()}</h3>

                  {nominee.description() ? (
                    <p className="NomineeCard-description">{nominee.description()}</p>
                  ) : null}

                  {isWinner ? (
                    <div className="TagLabel Label--success">
                      {app.translator.trans('huseyinfiliz-awards.forum.results.winner')}
                    </div>
                  ) : null}
                  {isRunnerUp ? (
                    <div className="TagLabel Label--info">
                      {app.translator.trans('huseyinfiliz-awards.forum.results.runner_up')}
                    </div>
                  ) : null}

                  {canShowVotes ? (
                    <div className="NomineeCard-stats">
                      <span className="NomineeCard-votes">
                        {app.translator.trans('huseyinfiliz-awards.forum.results.votes', { count: nominee.voteCount() })}
                      </span>
                      <span className="NomineeCard-percentage">
                        {app.translator.trans('huseyinfiliz-awards.forum.results.percentage', { percent: nominee.votePercentage() })}
                      </span>
                    </div>
                  ) : null}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    );
  }
}
