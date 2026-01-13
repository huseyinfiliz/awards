import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Stream from 'flarum/common/utils/Stream';
import OtherSuggestion from '../../../common/models/OtherSuggestion';
import Nominee from '../../../common/models/Nominee';

interface MergeModalAttrs {
  suggestion: OtherSuggestion;
  onmerge?: () => void;
}

export default class MergeModal extends Modal<MergeModalAttrs> {
  loading: boolean = true;
  nominees: Nominee[] = [];
  selectedNomineeId: Stream<string>;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.selectedNomineeId = Stream('');
    this.loadNominees();
  }

  className() {
    return 'HFAwardsModal HFAwardsMergeModal Modal--small';
  }

  title() {
    return app.translator.trans('huseyinfiliz-awards.admin.suggestions.merge_title');
  }

  async loadNominees() {
    const categoryId = this.attrs.suggestion.category()?.id?.();
    if (!categoryId) {
      this.loading = false;
      m.redraw();
      return;
    }

    try {
      const nominees = await app.store.find<Nominee[]>('award-nominees', {
        filter: { category: categoryId },
      });
      this.nominees = nominees || [];
    } catch (error) {
      console.error('Failed to load nominees:', error);
      this.nominees = [];
    }

    this.loading = false;
    m.redraw();
  }

  content() {
    if (this.loading) {
      return (
        <div className="Modal-body">
          <LoadingIndicator />
        </div>
      );
    }

    if (this.nominees.length === 0) {
      return (
        <div className="Modal-body">
          <p className="MergeModal-empty">
            {app.translator.trans('huseyinfiliz-awards.admin.suggestions.no_nominees')}
          </p>
        </div>
      );
    }

    const suggestion = this.attrs.suggestion;

    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <p className="MergeModal-info">
              {app.translator.trans('huseyinfiliz-awards.admin.suggestions.merge_info', {
                name: <strong>{suggestion.name()}</strong>,
              })}
            </p>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.suggestions.select_nominee_label')}</label>
            <select
              className="FormControl"
              value={this.selectedNomineeId()}
              onchange={(e: Event) => {
                this.selectedNomineeId((e.target as HTMLSelectElement).value);
              }}
            >
              <option value="">
                {app.translator.trans('huseyinfiliz-awards.admin.suggestions.select_nominee_placeholder')}
              </option>
              {this.nominees.map((nominee) => (
                <option key={nominee.id()} value={String(nominee.id())}>
                  {nominee.name()}
                </option>
              ))}
            </select>
          </div>

          <div className="Form-group">
            <Button
              className="Button Button--primary"
              type="submit"
              loading={this.loading}
              disabled={!this.selectedNomineeId()}
            >
              {app.translator.trans('huseyinfiliz-awards.admin.suggestions.merge')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  async onsubmit(e: SubmitEvent) {
    e.preventDefault();

    if (!this.selectedNomineeId()) return;

    this.loading = true;
    m.redraw();

    try {
      await app.request({
        method: 'PATCH',
        url: app.forum.attribute('apiUrl') + '/award-other-suggestions/' + this.attrs.suggestion.id(),
        body: {
          data: {
            attributes: {
              action: 'merge',
              mergeToNomineeId: parseInt(this.selectedNomineeId(), 10),
            },
          },
        },
      });

      this.attrs.onmerge?.();
      app.modal.close();
    } catch (error) {
      this.loading = false;
      m.redraw();
      throw error;
    }
  }
}
