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
          <Button
            className="Button Button--primary"
            icon="fas fa-plus"
            onclick={() => this.openModal()}
            disabled={!this.selectedAwardId}
          >
            {app.translator.trans('huseyinfiliz-awards.admin.categories.create')}
          </Button>
        </div>

        <table className="CategoriesTab-table Table">
          <thead>
            <tr>
              <th style={{ width: '80px' }}>{app.translator.trans('huseyinfiliz-awards.admin.categories.sort_order')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.categories.name')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.categories.nominees')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.categories.votes')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.categories.allow_other')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.categories.actions')}</th>
            </tr>
          </thead>
          <tbody>
            {this.categories.length === 0 ? (
              <tr>
                <td colSpan={6} className="CategoriesTab-empty">
                  {app.translator.trans('huseyinfiliz-awards.admin.categories.empty')}
                </td>
              </tr>
            ) : (
              this.categories.map((category, index) => (
                <tr key={category.id()}>
                  <td className="CategoriesTab-sort">
                    <Button
                      className="Button Button--icon"
                      icon="fas fa-chevron-up"
                      onclick={() => this.moveCategory(category, 'up')}
                      disabled={index === 0}
                      title={app.translator.trans('huseyinfiliz-awards.admin.categories.move_up')}
                    />
                    <Button
                      className="Button Button--icon"
                      icon="fas fa-chevron-down"
                      onclick={() => this.moveCategory(category, 'down')}
                      disabled={index === this.categories.length - 1}
                      title={app.translator.trans('huseyinfiliz-awards.admin.categories.move_down')}
                    />
                  </td>
                  <td>
                    <strong>{category.name()}</strong>
                    <div className="helpText">{category.description()}</div>
                  </td>
                  <td>{category.nomineeCount?.() || 0}</td>
                  <td>{category.voteCount?.() || 0}</td>
                  <td>{category.allowOther() ? 'Yes' : 'No'}</td>
                  <td className="CategoriesTab-actions">
                    <Button className="Button Button--icon" icon="fas fa-edit" onclick={() => this.openModal(category)} />
                    <Button
                      className="Button Button--icon Button--danger"
                      icon="fas fa-trash"
                      onclick={() => this.deleteCategory(category)}
                    />
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
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
