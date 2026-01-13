import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
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

    // Check if user has voted for this nominee
    // Use direct ID attributes for reliable comparison (relationships may not be loaded)
    const userVotes = app.store.all<Vote>('award-votes');
    const nomineeId = nominee.id();
    const categoryId = category.id();

    const userVote = userVotes.find(v => {
      // First try direct attributes (most reliable)
      const vNomineeId = v.nomineeId?.() || v.data?.relationships?.nominee?.data?.id;
      const vCategoryId = v.categoryId?.() || v.data?.relationships?.category?.data?.id;
      return String(vNomineeId) === String(nomineeId) && String(vCategoryId) === String(categoryId);
    });
    const isVoted = !!userVote;
    const canVote = award.isVotingOpen() && app.session.user;

    return (
      <div className={`NomineeCard ${isVoted ? 'NomineeCard--voted' : ''}`}>
        <div className="NomineeCard-image">
            {nominee.imageUrl() ? (
                <img src={nominee.imageUrl()} alt={nominee.name()} />
            ) : (
                <div className="NomineeCard-placeholder">
                    <i className="fas fa-user" />
                </div>
            )}
        </div>

        <div className="NomineeCard-content">
            <h3 className="NomineeCard-title">{nominee.name()}</h3>

            <Button
                className={`Button ${isVoted ? 'Button--primary' : 'Button--text'}`}
                loading={this.loading}
                disabled={!canVote || this.loading}
                onclick={() => this.toggleVote(nominee, userVote)}
            >
                {isVoted
                    ? app.translator.trans('huseyinfiliz-awards.forum.voting.voted')
                    : app.translator.trans('huseyinfiliz-awards.forum.voting.select')}
            </Button>
        </div>
      </div>
    );
  }

  toggleVote(nominee: Nominee, userVote?: Vote) {
      if (!app.session.user) {
          // Trigger login modal or alert
          return;
      }

      this.loading = true;
      m.redraw();

      if (userVote) {
          // Delete existing vote
          userVote.delete().then(() => {
              this.loading = false;
              m.redraw();
          }).catch((e: any) => {
              this.loading = false;
              m.redraw();
              throw e;
          });
      } else {
          // Create new vote
          const category = this.attrs.category as Category;
          const categoryId = category.id();

          app.request({
              method: 'POST',
              url: app.forum.attribute('apiUrl') + '/award-votes',
              body: {
                  data: {
                      attributes: {
                          nomineeId: nominee.id()
                      }
                  }
              }
          }).then((response: any) => {
              // Remove any existing votes in this category from the store
              // (backend deletes them when votes_per_category=1, but frontend store doesn't know)
              const existingVotes = app.store.all<Vote>('award-votes').filter(v => {
                  const vCategoryId = v.categoryId?.() || v.data?.relationships?.category?.data?.id;
                  return String(vCategoryId) === String(categoryId);
              });
              existingVotes.forEach(v => app.store.remove(v));

              // Add the new vote to the store
              app.store.pushPayload(response);
              this.loading = false;
              m.redraw();
          }).catch((e: any) => {
              this.loading = false;
              m.redraw();
              throw e;
          });
      }
  }
}
