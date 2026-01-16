import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';
import Nominee from '../../common/models/Nominee';
import OtherSuggestion from '../../common/models/OtherSuggestion';
import MergeModal from './modals/MergeModal';

export default class SuggestionsTab extends Component {
  loading: boolean = true;
  awards: Award[] = [];
  categories: Category[] = [];
  nominees: Nominee[] = [];
  suggestions: OtherSuggestion[] = [];
  selectedAwardId: string = '';
  selectedCategoryId: string = '';

  oninit(vnode: any) {
    super.oninit(vnode);
    this.loadAwards();
  }

  async loadAwards() {
    this.loading = true;
    m.redraw();

    try {
      const awards = await app.store.find<Award[]>('awards');
      this.awards = awards || [];
      if (this.awards.length > 0 && !this.selectedAwardId) {
        this.selectedAwardId = String(this.awards[0].id());
        await this.loadCategories();
      }
    } catch (error) {
      console.error('Failed to load awards:', error);
    }

    this.loading = false;
    m.redraw();
  }

  async loadCategories() {
    if (!this.selectedAwardId) {
      this.categories = [];
      return;
    }

    try {
      const categories = await app.store.find<Category[]>('award-categories', {
        filter: { award: this.selectedAwardId },
      });
      this.categories = categories || [];
      this.selectedCategoryId = '';
      await this.loadSuggestions();
    } catch (error) {
      console.error('Failed to load categories:', error);
    }

    m.redraw();
  }

  async loadSuggestions() {
    try {
      const params: any = { filter: {} };
      if (this.selectedCategoryId) {
        params.filter.category = this.selectedCategoryId;
      } else if (this.selectedAwardId) {
        params.filter.award = this.selectedAwardId;
      }

      const suggestions = await app.store.find<OtherSuggestion[]>('award-other-suggestions', params);
      this.suggestions = suggestions || [];
    } catch (error) {
      console.error('Failed to load suggestions:', error);
      this.suggestions = [];
    }

    m.redraw();
  }

  view() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    const awardOptions: Record<string, string> = { '': 'All Awards' };
    this.awards.forEach((award) => {
      awardOptions[String(award.id())] = `${award.name()} (${award.year()})`;
    });

    const categoryOptions: Record<string, string> = { '': 'All Categories' };
    this.categories.forEach((category) => {
      categoryOptions[String(category.id())] = category.name() as string;
    });

    return (
      <div className="SuggestionsTab">
        <div className="SuggestionsTab-header">
          <Select
            value={this.selectedAwardId}
            options={awardOptions}
            onchange={(value: string) => {
              this.selectedAwardId = value;
              this.loadCategories();
            }}
          />
          <Select
            value={this.selectedCategoryId}
            options={categoryOptions}
            onchange={(value: string) => {
              this.selectedCategoryId = value;
              this.loadSuggestions();
            }}
            disabled={this.categories.length === 0}
          />
        </div>

        <div className="CardList">
          <div className="CardList-header">
            <div>{app.translator.trans('huseyinfiliz-awards.admin.suggestions.name')}</div>
            <div>{app.translator.trans('huseyinfiliz-awards.admin.suggestions.category')}</div>
            <div>{app.translator.trans('huseyinfiliz-awards.admin.suggestions.user')}</div>
            <div>{app.translator.trans('huseyinfiliz-awards.admin.suggestions.actions')}</div>
          </div>

          {this.suggestions.length === 0 ? (
            <div className="EmptyState">
              <i className="fas fa-lightbulb" />
              <p>{app.translator.trans('huseyinfiliz-awards.admin.suggestions.empty')}</p>
            </div>
          ) : (
            this.suggestions.map((suggestion) => (
              <div className="CardList-item" key={suggestion.id()}>
                <div className="CardList-item-cell CardList-item-cell--primary">
                  <div className="CardList-item-name">{suggestion.name()}</div>
                </div>
                <div className="CardList-item-cell CardList-item-cell--muted">{suggestion.category()?.name?.() || '-'}</div>
                <div className="CardList-item-cell CardList-item-cell--muted">{suggestion.user()?.displayName?.() || '-'}</div>
                <div className="CardList-item-actions">
                  <Button className="Button Button--success" icon="fas fa-check" onclick={() => this.approveSuggestion(suggestion)}>
                    {app.translator.trans('huseyinfiliz-awards.admin.suggestions.approve')}
                  </Button>
                  <Button className="Button Button--danger" icon="fas fa-times" onclick={() => this.rejectSuggestion(suggestion)}>
                    {app.translator.trans('huseyinfiliz-awards.admin.suggestions.reject')}
                  </Button>
                  <Button className="Button" icon="fas fa-compress-arrows-alt" onclick={() => this.openMergeModal(suggestion)}>
                    {app.translator.trans('huseyinfiliz-awards.admin.suggestions.merge')}
                  </Button>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    );
  }

  async approveSuggestion(suggestion: OtherSuggestion) {
    if (!confirm(app.translator.trans('huseyinfiliz-awards.admin.suggestions.approve_confirm') as string)) {
      return;
    }

    try {
      await app.request({
        method: 'PATCH',
        url: app.forum.attribute('apiUrl') + '/award-other-suggestions/' + suggestion.id(),
        body: { data: { attributes: { action: 'approve' } } },
      });
      this.loadSuggestions();
    } catch (error) {
      console.error('Failed to approve suggestion:', error);
    }
  }

  async rejectSuggestion(suggestion: OtherSuggestion) {
    if (!confirm(app.translator.trans('huseyinfiliz-awards.admin.suggestions.reject_confirm') as string)) {
      return;
    }

    try {
      await app.request({
        method: 'PATCH',
        url: app.forum.attribute('apiUrl') + '/award-other-suggestions/' + suggestion.id(),
        body: { data: { attributes: { action: 'reject' } } },
      });
      this.loadSuggestions();
    } catch (error) {
      console.error('Failed to reject suggestion:', error);
    }
  }

  openMergeModal(suggestion: OtherSuggestion) {
    app.modal.show(MergeModal, {
      suggestion,
      onmerge: () => this.loadSuggestions(),
    });
  }
}
