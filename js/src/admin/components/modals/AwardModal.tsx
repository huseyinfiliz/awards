import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import Award from '../../../common/models/Award';

interface AwardModalAttrs {
  award?: Award;
  onsubmit?: () => void;
}

export default class AwardModal extends Modal<AwardModalAttrs> {
  name: Stream<string>;
  slug: Stream<string>;
  year: Stream<number>;
  description: Stream<string>;
  startsAt: Stream<string>;
  endsAt: Stream<string>;
  status: Stream<string>;
  showLiveVotes: Stream<boolean>;
  imageUrl: Stream<string>;

  oninit(vnode: any) {
    super.oninit(vnode);

    const award = this.attrs.award;

    this.name = Stream(award?.name() || '');
    this.slug = Stream(award?.slug() || '');
    this.year = Stream(award?.year() || new Date().getFullYear());
    this.description = Stream(award?.description() || '');
    this.startsAt = Stream(award?.startsAt()?.toISOString().slice(0, 16) || '');
    this.endsAt = Stream(award?.endsAt()?.toISOString().slice(0, 16) || '');
    this.status = Stream(award?.status() || 'draft');
    this.showLiveVotes = Stream(award?.showLiveVotes() || false);
    this.imageUrl = Stream(award?.imageUrl() || '');
  }

  className() {
    return 'HFAwardsModal HFAwardsAwardModal Modal--large';
  }

  title() {
    return this.attrs.award
      ? app.translator.trans('huseyinfiliz-awards.admin.awards.edit_title')
      : app.translator.trans('huseyinfiliz-awards.admin.awards.create_title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.awards.name')}</label>
            <input className="FormControl" bidi={this.name} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.awards.slug')}</label>
            <input className="FormControl" bidi={this.slug} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.awards.year')}</label>
            <input className="FormControl" type="number" bidi={this.year} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.awards.description')}</label>
            <textarea className="FormControl" bidi={this.description} rows={3} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.awards.starts_at')}</label>
            <input className="FormControl" type="datetime-local" bidi={this.startsAt} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.awards.ends_at')}</label>
            <input className="FormControl" type="datetime-local" bidi={this.endsAt} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.awards.status')}</label>
            <select className="FormControl" value={this.status()} onchange={(e: Event) => this.status((e.target as HTMLSelectElement).value)}>
              <option value="draft">{app.translator.trans('huseyinfiliz-awards.admin.awards.status_draft')}</option>
              <option value="active">{app.translator.trans('huseyinfiliz-awards.admin.awards.status_active')}</option>
              <option value="ended">{app.translator.trans('huseyinfiliz-awards.admin.awards.status_ended')}</option>
              <option value="published">{app.translator.trans('huseyinfiliz-awards.admin.awards.status_published')}</option>
            </select>
          </div>

          <div className="Form-group">
            <label className="checkbox">
              <input
                type="checkbox"
                checked={this.showLiveVotes()}
                onchange={(e: Event) => this.showLiveVotes((e.target as HTMLInputElement).checked)}
              />
              {app.translator.trans('huseyinfiliz-awards.admin.awards.show_live_votes')}
            </label>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.awards.image_url')}</label>
            <input className="FormControl" bidi={this.imageUrl} />
          </div>

          <div className="Form-group">
            <Button className="Button Button--primary" type="submit" loading={this.loading}>
              {app.translator.trans('huseyinfiliz-awards.lib.buttons.save')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  async onsubmit(e: SubmitEvent) {
    e.preventDefault();
    this.loading = true;

    const data: any = {
      name: this.name(),
      slug: this.slug() || this.name().toLowerCase().replace(/\s+/g, '-'),
      year: this.year(),
      description: this.description(),
      startsAt: this.startsAt() ? new Date(this.startsAt()).toISOString() : null,
      endsAt: this.endsAt() ? new Date(this.endsAt()).toISOString() : null,
      status: this.status(),
      showLiveVotes: this.showLiveVotes(),
      imageUrl: this.imageUrl(),
    };

    try {
      if (this.attrs.award) {
        await this.attrs.award.save(data);
      } else {
        await app.store.createRecord('awards').save(data);
      }

      this.attrs.onsubmit?.();
      app.modal.close();
    } catch (error) {
      this.loading = false;
      m.redraw();
      throw error;
    }
  }
}
