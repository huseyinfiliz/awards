import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import Nominee from '../../../common/models/Nominee';

interface NomineeSuggestion {
  name: string;
  description: string | null;
  imageUrl: string | null;
}

interface NomineeModalAttrs {
  nominee?: Nominee;
  categoryId: string;
  onsubmit?: () => void;
}

export default class NomineeModal extends Modal<NomineeModalAttrs> {
  name: Stream<string>;
  description: Stream<string>;
  slug: Stream<string>;
  imageUrl: Stream<string>;
  sortOrder: Stream<number>;
  suggestions: Stream<NomineeSuggestion[]>;
  showSuggestions: Stream<boolean>;
  uploadLoading: boolean = false;

  oninit(vnode: any) {
    super.oninit(vnode);

    const nominee = this.attrs.nominee;

    this.name = Stream(nominee?.name() || '');
    this.description = Stream(nominee?.description() || '');
    this.slug = Stream(nominee?.slug() || '');
    this.imageUrl = Stream(nominee?.imageUrl() || '');
    this.sortOrder = Stream(nominee?.sortOrder() || 0);
    this.suggestions = Stream<NomineeSuggestion[]>([]);
    this.showSuggestions = Stream(false);
  }

  className() {
    return 'HFAwardsModal HFAwardsNomineeModal';
  }

  title() {
    return this.attrs.nominee
      ? app.translator.trans('huseyinfiliz-awards.admin.nominees.edit_title')
      : app.translator.trans('huseyinfiliz-awards.admin.nominees.create_title');
  }

  async searchNominees(query: string) {
    if (query.length < 2) {
      this.suggestions([]);
      this.showSuggestions(false);
      m.redraw();
      return;
    }

    try {
      const response = await app.request<any>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/award-nominees/autocomplete',
        params: { 'filter[q]': query },
      });

      const items: NomineeSuggestion[] = (response.data || [])
        .map((n: any) => ({
          name: n.attributes?.name || '',
          description: n.attributes?.description || null,
          imageUrl: n.attributes?.imageUrl || null,
        }))
        .filter((item: NomineeSuggestion) => item.name && item.name.toLowerCase() !== query.toLowerCase());

      const uniqueItems = items.filter(
        (item, index, self) => index === self.findIndex((t) => t.name === item.name)
      );

      this.suggestions(uniqueItems);
      this.showSuggestions(uniqueItems.length > 0);
    } catch (error) {
      console.error('Autocomplete error:', error);
      this.suggestions([]);
      this.showSuggestions(false);
    }

    m.redraw();
  }

  selectSuggestion(suggestion: NomineeSuggestion) {
    this.name(suggestion.name);
    if (suggestion.description) {
      this.description(suggestion.description);
    }
    if (suggestion.imageUrl) {
      this.imageUrl(suggestion.imageUrl);
    }
    this.showSuggestions(false);
    this.suggestions([]);
    m.redraw();
  }

  async handleUpload(e: Event) {
    const target = e.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];
    const formData = new FormData();
    formData.append('files[]', file);

    this.uploadLoading = true;
    m.redraw();

    try {
      const response: any = await app.request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/fof/upload',
        body: formData,
        serialize: (raw: any) => raw,
      });

      if (response && response.data && response.data[0] && response.data[0].attributes) {
        this.imageUrl(response.data[0].attributes.url);
        app.alerts.show({ type: 'success' }, app.translator.trans('huseyinfiliz-awards.admin.nominees.upload_success'));
      }
    } catch (error: any) {
      console.error('Image upload failed:', error);
      app.alerts.show({ type: 'error' }, app.translator.trans('huseyinfiliz-awards.admin.nominees.upload_error'));
    } finally {
      this.uploadLoading = false;
      target.value = '';
      m.redraw();
    }
  }

  content() {
    const hasFofUpload = 'fof-upload' in (app.data.extensions || {});

    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group" style={{ position: 'relative' }}>
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.name')}</label>
            <input
              className="FormControl"
              value={this.name()}
              oninput={(e: InputEvent) => {
                const value = (e.target as HTMLInputElement).value;
                this.name(value);
                this.searchNominees(value);
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
                {this.suggestions().map((suggestion: NomineeSuggestion, index: number) => {
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
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.description')}</label>
            <textarea
              className="FormControl"
              rows={2}
              value={this.description()}
              oninput={(e: InputEvent) => {
                this.description((e.target as HTMLTextAreaElement).value);
              }}
            />
            <div className="helpText">
              {app.translator.trans('huseyinfiliz-awards.admin.nominees.description_help')}
            </div>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.slug')}</label>
            <input className="FormControl" bidi={this.slug} />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.nominees.image_url')}</label>
            <div style={{ display: 'flex', gap: '8px' }}>
              <input
                className="FormControl"
                value={this.imageUrl()}
                oninput={(e: InputEvent) => {
                  this.imageUrl((e.target as HTMLInputElement).value);
                }}
                placeholder="https://example.com/image.png"
                style={{ flex: 1 }}
              />
              {hasFofUpload ? (
                <span>
                  <input
                    id="nominee-image-upload"
                    type="file"
                    accept="image/*"
                    style={{ display: 'none' }}
                    onchange={this.handleUpload.bind(this)}
                  />
                  <Button
                    className="Button Button--icon"
                    icon="fas fa-cloud-upload-alt"
                    loading={this.uploadLoading}
                    onclick={() => {
                      const fileInput = document.getElementById('nominee-image-upload');
                      if (fileInput) fileInput.click();
                    }}
                    title={app.translator.trans('huseyinfiliz-awards.admin.nominees.upload_image')}
                    type="button"
                  />
                </span>
              ) : null}
            </div>
            {this.imageUrl() ? (
              <div style={{ marginTop: '10px' }}>
                <img
                  src={this.imageUrl()}
                  alt="Preview"
                  style={{
                    maxWidth: '100px',
                    maxHeight: '100px',
                    border: '1px solid #ddd',
                    padding: '5px',
                    borderRadius: '4px',
                  }}
                  onerror={(e: any) => {
                    e.target.style.display = 'none';
                  }}
                />
              </div>
            ) : null}
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
      description: this.description(),
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
