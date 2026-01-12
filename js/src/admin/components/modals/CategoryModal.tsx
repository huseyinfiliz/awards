import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import Category from '../../../common/models/Category';

interface CategoryModalAttrs {
  category?: Category;
  awardId: string;
  onsubmit?: () => void;
}

export default class CategoryModal extends Modal<CategoryModalAttrs> {
  name: Stream<string>;
  slug: Stream<string>;
  description: Stream<string>;
  sortOrder: Stream<number>;
  allowOther: Stream<boolean>;

  oninit(vnode: any) {
    super.oninit(vnode);

    const category = this.attrs.category;

    this.name = Stream(category?.name() || '');
    this.slug = Stream(category?.slug() || '');
    this.description = Stream(category?.description() || '');
    this.sortOrder = Stream(category?.sortOrder() || 0);
    this.allowOther = Stream(category?.allowOther() || false);
  }

  className() {
    return 'CategoryModal';
  }

  title() {
    return this.attrs.category
      ? app.translator.trans('huseyinfiliz-awards.admin.categories.edit_title')
      : app.translator.trans('huseyinfiliz-awards.admin.categories.create_title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.categories.name')}</label>
            <input className="FormControl" bidi={this.name} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.categories.slug')}</label>
            <input className="FormControl" bidi={this.slug} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.categories.description')}</label>
            <textarea className="FormControl" bidi={this.description} rows={3} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.categories.sort_order')}</label>
            <input className="FormControl" type="number" bidi={this.sortOrder} />
          </div>

          <div className="Form-group">
            <label className="checkbox">
              <input
                type="checkbox"
                checked={this.allowOther()}
                onchange={(e: Event) => this.allowOther((e.target as HTMLInputElement).checked)}
              />
              {app.translator.trans('huseyinfiliz-awards.admin.categories.allow_other')}
            </label>
            <div className="helpText">
              {app.translator.trans('huseyinfiliz-awards.admin.categories.allow_other_help')}
            </div>
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
      description: this.description(),
      sortOrder: this.sortOrder(),
      allowOther: this.allowOther(),
    };

    if (!this.attrs.category) {
      data.relationships = {
        award: { data: { type: 'awards', id: this.attrs.awardId } },
      };
    }

    try {
      if (this.attrs.category) {
        await this.attrs.category.save(data);
      } else {
        await app.store.createRecord('award-categories').save(data);
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
