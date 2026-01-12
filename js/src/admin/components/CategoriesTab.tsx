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
        filter: { award_id: this.selectedAwardId },
      });
      this.categories = categories || [];
    } catch (error) {
      console.error('Failed to load categories:', error);
      this.categories = [];
    }

    m.redraw();
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
                <td colSpan={5} className="CategoriesTab-empty">
                  {app.translator.trans('huseyinfiliz-awards.admin.categories.empty')}
                </td>
              </tr>
            ) : (
              this.categories.map((category) => (
                <tr key={category.id()}>
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
