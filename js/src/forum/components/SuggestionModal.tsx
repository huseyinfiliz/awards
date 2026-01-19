import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import Alert from 'flarum/common/components/Alert';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Category from '../../common/models/Category';
import OtherSuggestion from '../../common/models/OtherSuggestion';

export default class SuggestionModal extends Modal {
  category!: Category;
  name!: Stream<string>;
  suggestions: OtherSuggestion[] = [];
  loadingSuggestions: boolean = true;
  cancellingId: string | null = null;

  oninit(vnode: any) {
    super.oninit(vnode);

    this.category = this.attrs.category;
    this.name = Stream('');
    this.loadSuggestions();
  }

  className() {
    return 'HFAwardsModal HFAwardsSuggestionModal Modal--small';
  }

  title() {
    return app.translator.trans('huseyinfiliz-awards.forum.other.suggest');
  }

  async loadSuggestions() {
    this.loadingSuggestions = true;
    m.redraw();

    try {
      const response = await app.request<any>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/award-other-suggestions/mine',
        params: { 'filter[category]': this.category.id() },
      });

      this.suggestions = (response.data || []).map((item: any) => {
        return app.store.pushObject(item);
      });
    } catch (error) {
      console.error('Failed to load suggestions:', error);
      this.suggestions = [];
    }

    this.loadingSuggestions = false;
    m.redraw();
  }

  getRemainingSlots(): number {
    const userVoteCount = (this.category.userVoteIds() || []).length;
    const votesPerCategory = parseInt(app.forum.attribute('awardsVotesPerCategory') || '1', 10);

    if (votesPerCategory === 0) return Infinity;

    // Use the locally loaded suggestions list for accurate pending count
    const pendingCount = this.suggestions.filter((s) => s.isPending()).length;

    return Math.max(0, votesPerCategory - userVoteCount - pendingCount);
  }

  updateCategoryPendingCount() {
    // Update the category model in the store with the new pending count
    const pendingCount = this.suggestions.filter((s) => s.isPending()).length;

    // Update the category's data in the store
    this.category.pushData({
      attributes: {
        userPendingSuggestionsCount: pendingCount,
      },
    });
  }

  content() {
    // Show loading state while suggestions are being fetched
    if (this.loadingSuggestions) {
      return (
        <div className="Modal-body">
          <LoadingIndicator />
        </div>
      );
    }

    const remainingSlots = this.getRemainingSlots();
    const isUnlimited = parseInt(app.forum.attribute('awardsVotesPerCategory') || '1', 10) === 0;
    const canSubmit = isUnlimited || remainingSlots > 0;

    return (
      <div className="Modal-body">
        {/* Submission Form */}
        {canSubmit ? (
          <div className="SuggestionModal-form">
            <div className="Form-group">
              <label>{app.translator.trans('huseyinfiliz-awards.forum.other.placeholder')}</label>
              <input className="FormControl" bidi={this.name} required />
            </div>
            <div className="Form-group">
              <Button className="Button Button--primary" type="submit" loading={this.loading} disabled={!this.name()}>
                {app.translator.trans('huseyinfiliz-awards.forum.other.submit')}
              </Button>
              {!isUnlimited && (
                <span className="SuggestionModal-remaining">
                  {app.translator.trans('huseyinfiliz-awards.forum.other.remaining', { count: remainingSlots })}
                </span>
              )}
            </div>
          </div>
        ) : (
          <div className="Form-group">
            <Alert type="warning" dismissible={false}>
              {app.translator.trans('huseyinfiliz-awards.forum.other.no_remaining')}
            </Alert>
          </div>
        )}

        {/* User's Suggestions List */}
        <div className="SuggestionModal-list">
          <h4 className="SuggestionModal-listTitle">{app.translator.trans('huseyinfiliz-awards.forum.other.my_suggestions')}</h4>

          {this.suggestions.length === 0 ? (
            <p className="SuggestionModal-empty">{app.translator.trans('huseyinfiliz-awards.forum.other.no_suggestions')}</p>
          ) : (
            <ul className="SuggestionModal-suggestions">{this.suggestions.map((suggestion) => this.renderSuggestionItem(suggestion))}</ul>
          )}
        </div>
      </div>
    );
  }

  renderSuggestionItem(suggestion: OtherSuggestion) {
    const status = suggestion.status();
    const statusLabels: Record<string, string> = {
      pending: app.translator.trans('huseyinfiliz-awards.forum.other.status_pending') as string,
      approved: app.translator.trans('huseyinfiliz-awards.forum.other.status_approved') as string,
      rejected: app.translator.trans('huseyinfiliz-awards.forum.other.status_rejected') as string,
      merged: app.translator.trans('huseyinfiliz-awards.forum.other.status_merged') as string,
    };

    return (
      <li className="SuggestionModal-item" key={suggestion.id()}>
        <span className="SuggestionModal-itemName">{suggestion.name()}</span>
        <span className={`SuggestionModal-itemStatus SuggestionModal-itemStatus--${status}`}>{statusLabels[status] || status}</span>
        {suggestion.isPending() && (
          <Button
            className="Button Button--icon Button--link SuggestionModal-itemCancel"
            icon="fas fa-times"
            onclick={() => this.cancelSuggestion(suggestion)}
            loading={this.cancellingId === suggestion.id()}
            title={app.translator.trans('huseyinfiliz-awards.forum.other.cancel') as string}
          />
        )}
      </li>
    );
  }

  async cancelSuggestion(suggestion: OtherSuggestion) {
    if (!confirm(app.translator.trans('huseyinfiliz-awards.forum.other.cancel_confirm') as string)) {
      return;
    }

    this.cancellingId = suggestion.id() as string;
    m.redraw();

    try {
      await app.request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/award-other-suggestions/' + suggestion.id(),
      });

      // Remove from local list
      this.suggestions = this.suggestions.filter((s) => s.id() !== suggestion.id());

      // Update the category model in the store so OtherCard reflects the change
      this.updateCategoryPendingCount();
    } catch (error) {
      console.error('Failed to cancel suggestion:', error);
    }

    this.cancellingId = null;
    m.redraw();
  }

  onsubmit(e: any) {
    e.preventDefault();
    this.loading = true;

    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/award-other-suggestions',
        body: {
          data: {
            attributes: {
              categoryId: this.category.id(),
              name: this.name(),
            },
          },
        },
        errorHandler: this.onerror.bind(this),
      })
      .then((response: any) => {
        // Add to local list
        const newSuggestion = app.store.pushObject(response.data);
        this.suggestions.unshift(newSuggestion);
        this.name('');
        this.loading = false;

        // Update the category model in the store
        this.updateCategoryPendingCount();

        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
