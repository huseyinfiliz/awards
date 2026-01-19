import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Nominee from '../../../common/models/Nominee';

interface VoteAdjustmentModalAttrs {
  nominee: Nominee;
  onsubmit: () => void;
}

export default class VoteAdjustmentModal extends Modal<VoteAdjustmentModalAttrs> {
  adjustment: number = 0;
  loading: boolean = false;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.adjustment = this.attrs.nominee.voteAdjustment?.() ?? 0;
  }

  className() {
    return 'VoteAdjustmentModal Modal--small';
  }

  title() {
    return app.translator.trans('huseyinfiliz-awards.admin.nominees.vote_adjustment_title');
  }

  content() {
    const nominee = this.attrs.nominee;
    const realVotes = nominee.realVoteCount?.() ?? 0;
    const displayedVotes = realVotes + this.adjustment;

    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.real_votes')}</label>
            <div className="FormControl-static">{realVotes}</div>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.adjustment')}</label>
            <div className="VoteAdjustment-input">
              <input
                type="number"
                className="FormControl"
                value={this.adjustment}
                oninput={(e: InputEvent) => {
                  this.adjustment = parseInt((e.target as HTMLInputElement).value, 10) || 0;
                }}
              />
              <Button
                className="Button"
                onclick={() => {
                  this.adjustment = 0;
                }}
                title={app.translator.trans('huseyinfiliz-awards.admin.nominees.reset_adjustment') as string}
              >
                {app.translator.trans('huseyinfiliz-awards.admin.nominees.reset')}
              </Button>
            </div>
            <p className="helpText">{app.translator.trans('huseyinfiliz-awards.admin.nominees.adjustment_help')}</p>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.displayed_votes')}</label>
            <div className="FormControl-static VoteAdjustment-preview">
              {Math.max(0, displayedVotes)}
              {this.adjustment !== 0 && (
                <span className={`VoteAdjustment-badge ${this.adjustment > 0 ? 'positive' : 'negative'}`}>
                  {this.adjustment > 0 ? '+' : ''}
                  {this.adjustment}
                </span>
              )}
            </div>
          </div>

          <div className="Form-group">
            <Button className="Button Button--primary" type="submit" loading={this.loading} disabled={this.loading}>
              {app.translator.trans('huseyinfiliz-awards.lib.save')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  async onsubmit(e: SubmitEvent) {
    e.preventDefault();

    this.loading = true;
    m.redraw();

    try {
      await app.request({
        method: 'PATCH',
        url: app.forum.attribute('apiUrl') + '/award-nominees/' + this.attrs.nominee.id() + '/votes',
        body: { data: { attributes: { voteAdjustment: this.adjustment } } },
      });

      this.attrs.onsubmit();
      this.hide();
    } catch (error) {
      console.error('Failed to update vote adjustment:', error);
    }

    this.loading = false;
    m.redraw();
  }
}
