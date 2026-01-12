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
    const userVotes = app.store.all<Vote>('award-votes');
    const userVote = userVotes.find(v => v.category().id() === category.id() && v.nominee().id() === nominee.id());
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
              app.store.pushPayload(response);
              this.loading = false;
              m.redraw();
          }).catch((e: any) => {
              this.loading = false;
              m.redraw();
              // Error is handled globally by Flarum's request util usually, but createRecord/save handles it too.
              // Since we use raw request, we might need to rely on global error handler or show alert.
              throw e;
          });
      }
  }
}
