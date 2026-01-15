import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/helpers/humanTime';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';
import Nominee from '../../common/models/Nominee';
import Vote from '../../common/models/Vote';

interface VoteInfo {
  category: Category;
  nominee: Nominee;
  vote: Vote;
}

export default class MyVotesView extends Component {
  expandUnvoted: boolean = true;

  getVotesInfo(award: Award): { voted: VoteInfo[]; unvoted: Category[]; lastVoteTime: Date | null } {
    const categories = (award.categories() || []) as Category[];
    const userVotes = app.store.all<Vote>('award-votes');

    const voted: VoteInfo[] = [];
    const unvoted: Category[] = [];
    let lastVoteTime: Date | null = null;

    categories.forEach((category) => {
      const categoryId = category.id();
      const nominees = (category.nominees() || []) as Nominee[];

      // Find vote for this category
      const vote = userVotes.find((v) => {
        const vCategoryId = v.categoryId?.() || v.data?.relationships?.category?.data?.id;
        return String(vCategoryId) === String(categoryId);
      });

      if (vote) {
        // Find the voted nominee
        const vNomineeId = vote.nomineeId?.() || vote.data?.relationships?.nominee?.data?.id;
        const nominee = nominees.find((n) => String(n.id()) === String(vNomineeId));

        if (nominee) {
          voted.push({ category, nominee, vote });

          // Track last vote time
          const voteTime = vote.createdAt?.();
          if (voteTime && (!lastVoteTime || voteTime > lastVoteTime)) {
            lastVoteTime = voteTime;
          }
        }
      } else {
        unvoted.push(category);
      }
    });

    return { voted, unvoted, lastVoteTime };
  }

  view() {
    const award = this.attrs.award as Award;
    const onNavigateToCategory = this.attrs.onNavigateToCategory as (categoryId: string) => void;
    const categories = (award.categories() || []) as Category[];

    const { voted, unvoted, lastVoteTime } = this.getVotesInfo(award);
    const totalCategories = categories.length;
    const votedCount = voted.length;

    return (
      <div className="MyVotesView">
        {/* Summary Card */}
        <div className="MyVotesView-summary">
          <div className="MyVotesView-summary-content">
            <div className="MyVotesView-summary-stats">
              <span className="MyVotesView-summary-count">
                {app.translator.trans('huseyinfiliz-awards.forum.my_votes.summary', {
                  voted: votedCount,
                  total: totalCategories,
                })}
              </span>
              {lastVoteTime ? (
                <span className="MyVotesView-summary-time">
                  {app.translator.trans('huseyinfiliz-awards.forum.my_votes.last_vote', {
                    time: humanTime(lastVoteTime),
                  })}
                </span>
              ) : null}
            </div>
            <div className="MyVotesView-summary-progress">
              <div
                className="MyVotesView-summary-progressBar"
                style={{ width: `${totalCategories > 0 ? (votedCount / totalCategories) * 100 : 0}%` }}
              />
            </div>
          </div>
        </div>

        {/* Voted Categories */}
        {voted.length > 0 ? (
          <div className="MyVotesView-section">
            <h3 className="MyVotesView-section-title">
              {app.translator.trans('huseyinfiliz-awards.forum.my_votes.title')}
            </h3>
            <div className="MyVotesView-list">
              {voted.map(({ category, nominee, vote }) => (
                <div className="MyVotesView-item" key={category.id()}>
                  <div className="MyVotesView-item-nominee">
                    {nominee.imageUrl() ? (
                      <img
                        className="MyVotesView-item-image"
                        src={nominee.imageUrl()}
                        alt={nominee.name() as string}
                      />
                    ) : (
                      <div className="MyVotesView-item-placeholder">
                        <i className="fas fa-user" />
                      </div>
                    )}
                  </div>
                  <div className="MyVotesView-item-info">
                    <div className="MyVotesView-item-category">{category.name()}</div>
                    <div className="MyVotesView-item-name">{nominee.name()}</div>
                  </div>
                  <div className="MyVotesView-item-meta">
                    {vote.createdAt?.() ? (
                      <span className="MyVotesView-item-date">
                        {humanTime(vote.createdAt())}
                      </span>
                    ) : null}
                  </div>
                  <div className="MyVotesView-item-actions">
                    <Button
                      className="Button Button--text"
                      onclick={() => onNavigateToCategory(String(category.id()))}
                    >
                      {app.translator.trans('huseyinfiliz-awards.forum.my_votes.change')}
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ) : (
          <div className="MyVotesView-empty">
            <i className="fas fa-vote-yea" />
            <p>{app.translator.trans('huseyinfiliz-awards.forum.my_votes.no_votes')}</p>
          </div>
        )}

        {/* Unvoted Categories */}
        {unvoted.length > 0 ? (
          <div className="MyVotesView-section MyVotesView-section--unvoted">
            <button
              className="MyVotesView-section-header"
              onclick={() => {
                this.expandUnvoted = !this.expandUnvoted;
                m.redraw();
              }}
            >
              <h3 className="MyVotesView-section-title">
                {app.translator.trans('huseyinfiliz-awards.forum.my_votes.categories_remaining')}
                <span className="MyVotesView-section-count">({unvoted.length})</span>
              </h3>
              <i className={`fas fa-chevron-${this.expandUnvoted ? 'up' : 'down'}`} />
            </button>

            {this.expandUnvoted ? (
              <div className="MyVotesView-unvotedList">
                {unvoted.map((category) => (
                  <div className="MyVotesView-unvotedItem" key={category.id()}>
                    <span className="MyVotesView-unvotedItem-name">{category.name()}</span>
                    <Button
                      className="Button Button--primary Button--small"
                      onclick={() => onNavigateToCategory(String(category.id()))}
                    >
                      {app.translator.trans('huseyinfiliz-awards.forum.my_votes.vote_now')}
                    </Button>
                  </div>
                ))}
              </div>
            ) : null}
          </div>
        ) : null}
      </div>
    );
  }
}
