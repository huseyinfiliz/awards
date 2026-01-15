import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Nominee from '../../common/models/Nominee';
import Category from '../../common/models/Category';
import Award from '../../common/models/Award';
import Vote from '../../common/models/Vote';

export default class NomineeCard extends Component {
  loading = false;

  view() {
    const nominee = this.attrs.nominee as Nominee;
    const category = this.attrs.category as Category;
    const award = this.attrs.award as Award;

    const userVotes = app.store.all<Vote>('award-votes');
    const nomineeId = nominee.id();
    const categoryId = category.id();

    const userVote = userVotes.find((v) => {
      const vNomineeId = v.nomineeId?.() || v.data?.relationships?.nominee?.data?.id;
      const vCategoryId = v.categoryId?.() || v.data?.relationships?.category?.data?.id;
      return String(vNomineeId) === String(nomineeId) && String(vCategoryId) === String(categoryId);
    });

    const isVoted = !!userVote;
    const canVote = award.isVotingOpen() && app.session.user;
    const showVotes = award.canShowVotes();

    // Tıklanabilir mi kontrolü
    const isClickable = canVote && !this.loading;

    return (
      <div
        className={`NomineeCard ${isVoted ? 'NomineeCard--voted' : ''} ${isClickable ? 'NomineeCard--clickable' : ''} ${this.loading ? 'NomineeCard--loading' : ''}`}
        onclick={isClickable ? () => this.toggleVote(nominee, category, userVote) : undefined}
        role={isClickable ? 'button' : undefined}
        tabindex={isClickable ? 0 : undefined}
        onkeydown={isClickable ? (e: KeyboardEvent) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.toggleVote(nominee, category, userVote);
          }
        } : undefined}
      >
        <div className="NomineeCard-image">
          {nominee.imageUrl() ? (
            <img src={nominee.imageUrl()} alt={nominee.name()} loading="lazy" />
          ) : (
            <div className="NomineeCard-placeholder">
              <i className="fas fa-user" />
            </div>
          )}
          {isVoted ? (
            <div className="NomineeCard-check">
              <i className="fas fa-check" />
            </div>
          ) : null}
        </div>

        <div className="NomineeCard-content">
          <h3 className="NomineeCard-title">{nominee.name()}</h3>
          
          {nominee.description() ? (
            <p className="NomineeCard-description">{nominee.description()}</p>
          ) : null}

          {showVotes ? (
            <div className="NomineeCard-stats">
              <span className="NomineeCard-votes">
                {app.translator.trans('huseyinfiliz-awards.forum.results.votes', { count: nominee.voteCount() || 0 })}
              </span>
            </div>
          ) : null}

          {this.loading ? (
            <div className="NomineeCard-loading">
              <i className="fas fa-spinner fa-spin" />
            </div>
          ) : null}
        </div>
      </div>
    );
  }

  toggleVote(nominee: Nominee, category: Category, userVote?: Vote) {
    if (!app.session.user) {
      return;
    }

    const categoryId = category.id();
    const votesLimit = parseInt(app.forum.attribute('awardsVotesPerCategory') || '1', 10);

    // If user already voted for this nominee, remove the vote
    if (userVote) {
      this.loading = true;
      m.redraw();

      userVote
        .delete()
        .then(() => {
          // Decrement vote count for the nominee
          const currentVoteCount = nominee.voteCount?.() || 0;
          if (currentVoteCount > 0) {
            nominee.pushData({ attributes: { voteCount: currentVoteCount - 1 } });
          }

          app.alerts.show({ type: 'success' }, app.translator.trans('huseyinfiliz-awards.forum.voting.vote_removed'));
          this.loading = false;
          m.redraw();
        })
        .catch((e: any) => {
          this.loading = false;
          m.redraw();
          throw e;
        });
      return;
    }

    // Adding a new vote - check limits for multi-vote mode
    const existingVotesInCategory = app.store.all<Vote>('award-votes').filter((v) => {
      const vCategoryId = v.categoryId?.() || v.data?.relationships?.category?.data?.id;
      return String(vCategoryId) === String(categoryId);
    });

    // Check if at limit (votesLimit > 0 means there's a limit, 0 = unlimited)
    if (votesLimit > 0 && existingVotesInCategory.length >= votesLimit && votesLimit !== 1) {
      app.alerts.show(
        { type: 'error' },
        app.translator.trans('huseyinfiliz-awards.forum.error.vote_limit_reached', { limit: votesLimit })
      );
      return;
    }

    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/award-votes',
        body: {
          data: {
            attributes: {
              nomineeId: nominee.id(),
            },
          },
        },
      })
      .then((response: any) => {
        // Only remove existing votes in single-vote mode (votesLimit === 1)
        if (votesLimit === 1) {
          existingVotesInCategory.forEach((v) => {
            const prevNomineeId = v.nomineeId?.() || v.data?.relationships?.nominee?.data?.id;
            if (prevNomineeId && String(prevNomineeId) !== String(nominee.id())) {
              const prevNominee = app.store.getById('award-nominees', String(prevNomineeId));
              if (prevNominee) {
                const currentCount = (prevNominee as any).voteCount?.() || 0;
                if (currentCount > 0) {
                  (prevNominee as any).pushData({ attributes: { voteCount: currentCount - 1 } });
                }
              }
            }
            app.store.remove(v);
          });
        }

        // Increment vote count for newly voted nominee
        const currentVoteCount = nominee.voteCount?.() || 0;
        nominee.pushData({ attributes: { voteCount: currentVoteCount + 1 } });

        app.store.pushPayload(response);
        app.alerts.show({ type: 'success' }, app.translator.trans('huseyinfiliz-awards.forum.voting.vote_saved'));
        this.loading = false;
        m.redraw();
      })
      .catch((e: any) => {
        this.loading = false;
        m.redraw();
        throw e;
      });
  }
}
