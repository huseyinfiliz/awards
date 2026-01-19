import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/helpers/humanTime';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';
import Nominee from '../../common/models/Nominee';
import Vote from '../../common/models/Vote';

interface VoteEntry {
  nominee: Nominee;
  vote: Vote;
}

interface CategoryVoteInfo {
  category: Category;
  votes: VoteEntry[];
  votesLimit: number;
  canAddMore: boolean;
}

export default class MyVotesView extends Component {
  expandUnvoted: boolean = true;
  removingVotes: Set<string> = new Set();

  getVotesInfo(award: Award): { votedCategories: CategoryVoteInfo[]; unvoted: Category[]; lastVoteTime: Date | null; totalVotes: number } {
    const categories = (award.categories() || []) as Category[];
    const userVotes = app.store.all<Vote>('award-votes');
    const votesLimit = parseInt(app.forum.attribute('awardsVotesPerCategory') || '1', 10);

    const votedCategories: CategoryVoteInfo[] = [];
    const unvoted: Category[] = [];
    let lastVoteTime: Date | null = null;
    let totalVotes = 0;

    categories.forEach((category) => {
      const categoryId = category.id();
      const nominees = (category.nominees() || []) as Nominee[];

      // Find ALL votes for this category (multi-vote support)
      const categoryVotes = userVotes.filter((v) => {
        const vCategoryId = v.categoryId?.() || v.data?.relationships?.category?.data?.id;
        return String(vCategoryId) === String(categoryId);
      });

      if (categoryVotes.length > 0) {
        const voteEntries: VoteEntry[] = [];

        categoryVotes.forEach((vote) => {
          const vNomineeId = vote.nomineeId?.() || vote.data?.relationships?.nominee?.data?.id;
          const nominee = nominees.find((n) => String(n.id()) === String(vNomineeId));

          if (nominee) {
            voteEntries.push({ nominee, vote });
            totalVotes++;

            // Track last vote time
            const voteTime = vote.createdAt?.();
            if (voteTime && (!lastVoteTime || voteTime > lastVoteTime)) {
              lastVoteTime = voteTime;
            }
          }
        });

        if (voteEntries.length > 0) {
          // Can add more if: unlimited (0) OR below limit
          const canAddMore = votesLimit === 0 || voteEntries.length < votesLimit;
          votedCategories.push({ category, votes: voteEntries, votesLimit, canAddMore });
        }
      } else {
        unvoted.push(category);
      }
    });

    return { votedCategories, unvoted, lastVoteTime, totalVotes };
  }

  removeVote(vote: Vote, nominee: Nominee) {
    const voteId = String(vote.id());
    if (this.removingVotes.has(voteId)) return;

    this.removingVotes.add(voteId);
    m.redraw();

    vote
      .delete()
      .then(() => {
        // Decrement vote count for the nominee
        const currentVoteCount = nominee.voteCount?.() || 0;
        if (currentVoteCount > 0) {
          nominee.pushData({ attributes: { voteCount: currentVoteCount - 1 } });
        }

        app.alerts.show({ type: 'success' }, app.translator.trans('huseyinfiliz-awards.forum.voting.vote_removed'));
        this.removingVotes.delete(voteId);
        m.redraw();
      })
      .catch((e: any) => {
        this.removingVotes.delete(voteId);
        m.redraw();
        throw e;
      });
  }

  view() {
    const award = this.attrs.award as Award;
    const onNavigateToCategory = this.attrs.onNavigateToCategory as (categoryId: string) => void;
    const categories = (award.categories() || []) as Category[];
    const isVotingOpen = award.isVotingOpen();

    const { votedCategories, unvoted, lastVoteTime, totalVotes } = this.getVotesInfo(award);
    const totalCategories = categories.length;
    const votedCategoryCount = votedCategories.length;

    return (
      <div className="MyVotesView">
        {/* Summary Card */}
        <div className="MyVotesView-summary">
          <div className="MyVotesView-summary-content">
            <div className="MyVotesView-summary-stats">
              <span className="MyVotesView-summary-count">
                {app.translator.trans('huseyinfiliz-awards.forum.my_votes.summary', {
                  voted: votedCategoryCount,
                  total: totalCategories,
                })}
              </span>
              <span className="MyVotesView-summary-totalVotes">
                {app.translator.trans('huseyinfiliz-awards.forum.my_votes.total_votes', {
                  count: totalVotes,
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
                style={{ width: `${totalCategories > 0 ? (votedCategoryCount / totalCategories) * 100 : 0}%` }}
              />
            </div>
          </div>
        </div>

        {/* Voted Categories */}
        {votedCategories.length > 0 ? (
          <div className="MyVotesView-section">
            <h3 className="MyVotesView-section-title">{app.translator.trans('huseyinfiliz-awards.forum.my_votes.title')}</h3>
            <div className="MyVotesView-list">
              {votedCategories.map(({ category, votes, votesLimit, canAddMore }) => (
                <div className="MyVotesView-categoryGroup" key={category.id()}>
                  <div className="MyVotesView-categoryHeader">
                    <span className="MyVotesView-categoryName">{category.name()}</span>
                    <span className="MyVotesView-categoryVoteCount">
                      {votesLimit === 0
                        ? app.translator.trans('huseyinfiliz-awards.forum.my_votes.votes_unlimited', {
                            count: votes.length,
                          })
                        : app.translator.trans('huseyinfiliz-awards.forum.my_votes.votes_used', {
                            used: votes.length,
                            limit: votesLimit,
                          })}
                    </span>
                  </div>
                  {votes.map(({ nominee, vote }) => (
                    <div className="MyVotesView-item" key={vote.id()}>
                      <div className="MyVotesView-item-nominee">
                        {nominee.imageUrl() ? (
                          <img className="MyVotesView-item-image" src={nominee.imageUrl()} alt={nominee.name() as string} />
                        ) : (
                          <div className="MyVotesView-item-placeholder">
                            <i className="fas fa-user" />
                          </div>
                        )}
                      </div>
                      <div className="MyVotesView-item-info">
                        <div className="MyVotesView-item-name">{nominee.name()}</div>
                      </div>
                      <div className="MyVotesView-item-meta">
                        {vote.createdAt?.() ? <span className="MyVotesView-item-date">{humanTime(vote.createdAt())}</span> : null}
                      </div>
                      <div className="MyVotesView-item-actions">
                        {isVotingOpen ? (
                          <Button
                            className="Button Button--text Button--danger"
                            onclick={() => this.removeVote(vote, nominee)}
                            loading={this.removingVotes.has(String(vote.id()))}
                          >
                            {app.translator.trans('huseyinfiliz-awards.forum.my_votes.remove')}
                          </Button>
                        ) : null}
                      </div>
                    </div>
                  ))}
                  {/* Add more votes button if allowed */}
                  {isVotingOpen && canAddMore ? (
                    <div className="MyVotesView-addMore">
                      <Button className="Button Button--text" icon="fas fa-plus" onclick={() => onNavigateToCategory(String(category.id()))}>
                        {app.translator.trans('huseyinfiliz-awards.forum.my_votes.add_vote')}
                      </Button>
                    </div>
                  ) : null}
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
                    <Button className="Button Button--primary Button--small" onclick={() => onNavigateToCategory(String(category.id()))}>
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
