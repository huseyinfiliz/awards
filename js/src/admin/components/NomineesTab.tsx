import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import NomineeModal from './modals/NomineeModal';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';
import Nominee from '../../common/models/Nominee';

export default class NomineesTab extends Component {
  loading: boolean = true;
  awards: Award[] = [];
  categories: Category[] = [];
  nominees: Nominee[] = [];
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
      this.nominees = [];
      return;
    }

    try {
      const categories = await app.store.find<Category[]>('award-categories', {
        filter: { award_id: this.selectedAwardId },
      });
      this.categories = categories || [];
      if (this.categories.length > 0) {
        this.selectedCategoryId = String(this.categories[0].id());
        await this.loadNominees();
      } else {
        this.selectedCategoryId = '';
        this.nominees = [];
      }
    } catch (error) {
      console.error('Failed to load categories:', error);
    }

    m.redraw();
  }

  async loadNominees() {
    if (!this.selectedCategoryId) {
      this.nominees = [];
      return;
    }

    try {
      const nominees = await app.store.find<Nominee[]>('award-nominees', {
        filter: { category_id: this.selectedCategoryId },
      });
      this.nominees = nominees || [];
    } catch (error) {
      console.error('Failed to load nominees:', error);
      this.nominees = [];
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

    const categoryOptions: Record<string, string> = {};
    this.categories.forEach((category) => {
      categoryOptions[String(category.id())] = category.name();
    });

    return (
      <div className="NomineesTab">
        <div className="NomineesTab-header">
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
              this.loadNominees();
            }}
            disabled={this.categories.length === 0}
          />
          <Button
            className="Button Button--primary"
            icon="fas fa-plus"
            onclick={() => this.openModal()}
            disabled={!this.selectedCategoryId}
          >
            {app.translator.trans('huseyinfiliz-awards.admin.nominees.create')}
          </Button>
        </div>

        <table className="NomineesTab-table Table">
          <thead>
            <tr>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.nominees.image')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.nominees.name')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.nominees.votes')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.nominees.actions')}</th>
            </tr>
          </thead>
          <tbody>
            {this.nominees.length === 0 ? (
              <tr>
                <td colSpan={4} className="NomineesTab-empty">
                  {app.translator.trans('huseyinfiliz-awards.admin.nominees.empty')}
                </td>
              </tr>
            ) : (
              this.nominees.map((nominee) => (
                <tr key={nominee.id()}>
                  <td>
                    {nominee.imageUrl() ? (
                      <img src={nominee.imageUrl()} alt={nominee.name()} className="NomineeImage" />
                    ) : (
                      <div className="NomineeImage NomineeImage--placeholder">
                        <i className="fas fa-image"></i>
                      </div>
                    )}
                  </td>
                  <td>
                    <strong>{nominee.name()}</strong>
                  </td>
                  <td>
                    <span className="NomineeVotes">{nominee.voteCount?.() || 0}</span>
                    <Button
                      className="Button Button--icon Button--link"
                      icon="fas fa-edit"
                      onclick={() => this.editVotes(nominee)}
                      title={app.translator.trans('huseyinfiliz-awards.admin.nominees.edit_votes')}
                    />
                  </td>
                  <td className="NomineesTab-actions">
                    <Button className="Button Button--icon" icon="fas fa-edit" onclick={() => this.openModal(nominee)} />
                    <Button
                      className="Button Button--icon Button--danger"
                      icon="fas fa-trash"
                      onclick={() => this.deleteNominee(nominee)}
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

  openModal(nominee?: Nominee) {
    app.modal.show(NomineeModal, {
      nominee,
      categoryId: this.selectedCategoryId,
      onsubmit: () => this.loadNominees(),
    });
  }

  async editVotes(nominee: Nominee) {
    const currentVotes = nominee.voteCount?.() || 0;
    const newVotes = prompt(
      app.translator.trans('huseyinfiliz-awards.admin.nominees.enter_votes') as string,
      String(currentVotes)
    );

    if (newVotes === null) return;

    const votes = parseInt(newVotes, 10);
    if (isNaN(votes) || votes < 0) {
      alert(app.translator.trans('huseyinfiliz-awards.admin.nominees.invalid_votes') as string);
      return;
    }

    try {
      await app.request({
        method: 'PATCH',
        url: app.forum.attribute('apiUrl') + '/award-nominees/' + nominee.id() + '/votes',
        body: { data: { attributes: { voteCount: votes } } },
      });
      this.loadNominees();
    } catch (error) {
      console.error('Failed to update votes:', error);
    }
  }

  async deleteNominee(nominee: Nominee) {
    if (!confirm(app.translator.trans('huseyinfiliz-awards.admin.nominees.delete_confirm') as string)) {
      return;
    }

    try {
      await nominee.delete();
      this.loadNominees();
    } catch (error) {
      console.error('Failed to delete nominee:', error);
    }
  }
}
