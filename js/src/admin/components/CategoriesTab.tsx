import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import CategoryModal from './modals/CategoryModal';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';

export default class CategoriesTab extends Component {
  loading: boolean = true;
  awards: Award[] = [];
  categories: Category[] = [];
  selectedAwardId: string = '';

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
      this.categories = (categories || []).sort((a, b) => (a.sortOrder() || 0) - (b.sortOrder() || 0));
    } catch (error) {
      console.error('Failed to load categories:', error);
      this.categories = [];
    }

    m.redraw();
  }

  async moveCategory(category: Category, direction: 'up' | 'down') {
    const index = this.categories.indexOf(category);
    if (index === -1) return;

    const newIndex = direction === 'up' ? index - 1 : index + 1;
    if (newIndex < 0 || newIndex >= this.categories.length) return;

    // Swap positions in array
    const temp = this.categories[index];
    this.categories[index] = this.categories[newIndex];
    this.categories[newIndex] = temp;

    // Reassign sortOrder based on new array positions
    try {
      const promises = this.categories.map((c, i) => c.save({ sortOrder: i }));
      await Promise.all(promises);
      await this.loadCategories();
    } catch (error) {
      console.error('Failed to reorder category:', error);
      await this.loadCategories(); // Reload to restore original order
    }
  }

  view() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    const awardOptions: Record<string, string> = {};
    this.awards.forEach((award) => {
      awardOptions[String(award.id())] = `${award.name()} (${award.year()})`;
    });

    return (
      <div className="CategoriesTab">
        <div className="CategoriesTab-header">
          <Select
            value={this.selectedAwardId}
            options={awardOptions}
            onchange={(value: string) => {
              this.selectedAwardId = value;
              this.loadCategories();
            }}
          />
          <Button className="Button Button--primary" icon="fas fa-plus" onclick={() => this.openModal()} disabled={!this.selectedAwardId}>
            {app.translator.trans('huseyinfiliz-awards.admin.categories.create')}
          </Button>
        </div>

        <div className="CardList">
          <div className="CardList-header">
            <div>{app.translator.trans('huseyinfiliz-awards.lib.sort_order')}</div>
            <div>{app.translator.trans('huseyinfiliz-awards.admin.categories.name')}</div>
            <div>
              {app.translator.trans('huseyinfiliz-awards.admin.categories.nominees')} / {app.translator.trans('huseyinfiliz-awards.lib.votes')}
            </div>
            <div>{app.translator.trans('huseyinfiliz-awards.lib.actions')}</div>
          </div>

          {this.categories.length === 0 ? (
            <div className="EmptyState">
              <i className="fas fa-folder" />
              <p>{app.translator.trans('huseyinfiliz-awards.admin.categories.empty')}</p>
            </div>
          ) : (
            this.categories.map((category, index) => (
              <div className="CardList-item" key={category.id()}>
                <div className="CardList-item-cell CardList-item-sort">
                  <Button
                    className="Button Button--icon"
                    icon="fas fa-chevron-up"
                    onclick={() => this.moveCategory(category, 'up')}
                    disabled={index === 0}
                    title={app.translator.trans('huseyinfiliz-awards.lib.move_up') as string}
                  />
                  <Button
                    className="Button Button--icon"
                    icon="fas fa-chevron-down"
                    onclick={() => this.moveCategory(category, 'down')}
                    disabled={index === this.categories.length - 1}
                    title={app.translator.trans('huseyinfiliz-awards.lib.move_down') as string}
                  />
                </div>
                <div className="CardList-item-cell CardList-item-cell--primary">
                  <div>
                    <div className="CardList-item-name">{category.name()}</div>
                    {category.description() ? <div className="CardList-item-meta">{category.description()}</div> : null}
                    {category.allowOther() ? (
                      <span className="StatusBadge StatusBadge--active" style={{ marginTop: '4px' }}>
                        <i className="fas fa-lightbulb" /> Suggestions
                      </span>
                    ) : null}
                  </div>
                </div>
                <div className="CardList-item-cell CardList-item-cell--muted">
                  <span title={app.translator.trans('huseyinfiliz-awards.admin.categories.nominees') as string}>
                    <i className="fas fa-users" /> {category.nomineeCount?.() || 0}
                  </span>
                  <span title={app.translator.trans('huseyinfiliz-awards.lib.votes') as string}>
                    <i className="fas fa-vote-yea" /> {category.voteCount?.() || 0}
                  </span>
                </div>
                <div className="CardList-item-actions">
                  <Button className="Button Button--icon" icon="fas fa-edit" onclick={() => this.openModal(category)} />
                  <Button className="Button Button--icon Button--danger" icon="fas fa-trash" onclick={() => this.deleteCategory(category)} />
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    );
  }

  openModal(category?: Category) {
    app.modal.show(CategoryModal, {
      category,
      awardId: this.selectedAwardId,
      onsubmit: () => this.loadCategories(),
    });
  }

  async deleteCategory(category: Category) {
    if (!confirm(app.translator.trans('huseyinfiliz-awards.admin.categories.delete_confirm') as string)) {
      return;
    }

    try {
      await category.delete();
      this.loadCategories();
    } catch (error) {
      console.error('Failed to delete category:', error);
    }
  }
}
