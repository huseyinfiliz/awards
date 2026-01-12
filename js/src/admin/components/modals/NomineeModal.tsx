import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import Nominee from '../../../common/models/Nominee';

interface NomineeModalAttrs {
  nominee?: Nominee;
  categoryId: string;
  onsubmit?: () => void;
}

export default class NomineeModal extends Modal<NomineeModalAttrs> {
  name: Stream<string>;
  slug: Stream<string>;
  imageUrl: Stream<string>;
  sortOrder: Stream<number>;

  oninit(vnode: any) {
    super.oninit(vnode);

    const nominee = this.attrs.nominee;

    this.name = Stream(nominee?.name() || '');
    this.slug = Stream(nominee?.slug() || '');
    this.imageUrl = Stream(nominee?.imageUrl() || '');
    this.sortOrder = Stream(nominee?.sortOrder() || 0);
  }

  className() {
    return 'NomineeModal';
  }

  title() {
    return this.attrs.nominee
      ? app.translator.trans('huseyinfiliz-awards.admin.nominees.edit_title')
      : app.translator.trans('huseyinfiliz-awards.admin.nominees.create_title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.name')}</label>
            <input className="FormControl" bidi={this.name} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.slug')}</label>
            <input className="FormControl" bidi={this.slug} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.image_url')}</label>
            <input className="FormControl" bidi={this.imageUrl} />
            <div className="helpText">
              {app.translator.trans('huseyinfiliz-awards.admin.nominees.image_url_help')}
            </div>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.sort_order')}</label>
            <input className="FormControl" type="number" bidi={this.sortOrder} />
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
      imageUrl: this.imageUrl(),
      sortOrder: this.sortOrder(),
    };

    if (!this.attrs.nominee) {
      data.relationships = {
        category: { data: { type: 'award-categories', id: this.attrs.categoryId } },
      };
    }

    try {
      if (this.attrs.nominee) {
        await this.attrs.nominee.save(data);
      } else {
        await app.store.createRecord('award-nominees').save(data);
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
