import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import Category from '../../../common/models/Category';

interface CategorySuggestion {
  name: string;
  description: string | null;
}

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
  suggestions: Stream<CategorySuggestion[]>;
  showSuggestions: Stream<boolean>;

  oninit(vnode: any) {
    super.oninit(vnode);

    const category = this.attrs.category;

    this.name = Stream(category?.name() || '');
    this.slug = Stream(category?.slug() || '');
    this.description = Stream(category?.description() || '');
    this.sortOrder = Stream(category?.sortOrder() || 0);
    this.allowOther = Stream(category?.allowOther() || false);
    this.suggestions = Stream<CategorySuggestion[]>([]);
    this.showSuggestions = Stream(false);
  }

  className() {
    return 'HFAwardsModal HFAwardsCategoryModal';
  }

  title() {
    return this.attrs.category
      ? app.translator.trans('huseyinfiliz-awards.admin.categories.edit_title')
      : app.translator.trans('huseyinfiliz-awards.admin.categories.create_title');
  }

  async searchCategories(query: string) {
    if (query.length < 2) {
      this.suggestions([]);
      this.showSuggestions(false);
      m.redraw();
      return;
    }

    try {
      const response = await app.request<any>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/award-categories/autocomplete',
        params: { 'filter[q]': query },
      });

      const items: CategorySuggestion[] = (response.data || [])
        .map((c: any) => ({
          name: c.attributes?.name || '',
          description: c.attributes?.description || null,
        }))
        .filter((item: CategorySuggestion) => item.name && item.name.toLowerCase() !== query.toLowerCase());

      // Remove duplicates by name
      const uniqueItems = items.filter((item, index, self) => index === self.findIndex((t) => t.name === item.name));

      this.suggestions(uniqueItems);
      this.showSuggestions(uniqueItems.length > 0);
    } catch (error) {
      console.error('Autocomplete error:', error);
      this.suggestions([]);
      this.showSuggestions(false);
    }

    m.redraw();
  }

  selectSuggestion(suggestion: CategorySuggestion) {
    this.name(suggestion.name);
    if (suggestion.description) {
      this.description(suggestion.description);
    }
    this.showSuggestions(false);
    this.suggestions([]);
    m.redraw();
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group" style={{ position: 'relative' }}>
            <label>{app.translator.trans('huseyinfiliz-awards.admin.categories.name')}</label>
            <input
              className="FormControl"
              value={this.name()}
              oninput={(e: InputEvent) => {
                const value = (e.target as HTMLInputElement).value;
                this.name(value);
                this.searchCategories(value);
              }}
              onfocus={() => {
                if (this.suggestions().length > 0) {
                  this.showSuggestions(true);
                  m.redraw();
                }
              }}
              onblur={() => {
                setTimeout(() => {
                  this.showSuggestions(false);
                  m.redraw();
                }, 200);
              }}
            />
            {this.showSuggestions() && this.suggestions().length > 0 ? (
              <ul
                className="Dropdown-menu"
                style={{
                  position: 'absolute',
                  top: '100%',
                  left: 0,
                  right: 0,
                  zIndex: 1000,
                  display: 'block',
                  maxHeight: '200px',
                  overflowY: 'auto',
                }}
              >
                {this.suggestions().map((suggestion: CategorySuggestion, index: number) => {
                  return (
                    <li key={index}>
                      <button
                        type="button"
                        className="Button Button--link"
                        onclick={() => this.selectSuggestion(suggestion)}
                        style={{ width: '100%', textAlign: 'left', padding: '8px 12px' }}
                      >
                        {String(suggestion.name)}
                      </button>
                    </li>
                  );
                })}
              </ul>
            ) : null}
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.lib.slug')}</label>
            <input className="FormControl" bidi={this.slug} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.lib.description')}</label>
            <textarea className="FormControl" bidi={this.description} rows={3} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.lib.sort_order')}</label>
            <input className="FormControl" type="number" bidi={this.sortOrder} />
          </div>

          <div className="Form-group">
            <label className="checkbox">
              <input type="checkbox" checked={this.allowOther()} onchange={(e: Event) => this.allowOther((e.target as HTMLInputElement).checked)} />
              {app.translator.trans('huseyinfiliz-awards.admin.categories.allow_other')}
            </label>
            <div className="helpText">{app.translator.trans('huseyinfiliz-awards.admin.categories.allow_other_help')}</div>
          </div>

          <div className="Form-group">
            <Button className="Button Button--primary" type="submit" loading={this.loading}>
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
