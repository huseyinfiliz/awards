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
        filter: { award: this.selectedAwardId },
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
        filter: { category: this.selectedCategoryId },
      });
      this.nominees = (nominees || []).sort((a, b) => (a.sortOrder() || 0) - (b.sortOrder() || 0));
    } catch (error) {
      console.error('Failed to load nominees:', error);
      this.nominees = [];
    }

    m.redraw();
  }

  async moveNominee(nominee: Nominee, direction: 'up' | 'down') {
    const index = this.nominees.indexOf(nominee);
    if (index === -1) return;

    const newIndex = direction === 'up' ? index - 1 : index + 1;
    if (newIndex < 0 || newIndex >= this.nominees.length) return;

    // Swap positions in array
    const temp = this.nominees[index];
    this.nominees[index] = this.nominees[newIndex];
    this.nominees[newIndex] = temp;

    // Reassign sortOrder based on new array positions
    try {
      const promises = this.nominees.map((n, i) => n.save({ sortOrder: i }));
      await Promise.all(promises);
      await this.loadNominees();
    } catch (error) {
      console.error('Failed to reorder nominee:', error);
      await this.loadNominees(); // Reload to restore original order
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

    const categoryOptions: Record<string, string> = {};
    this.categories.forEach((category) => {
      categoryOptions[String(category.id())] = category.name() as string;
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

        <div className="CardList">
          <div className="CardList-header">
            <div style={{ width: '60px' }}></div>
            <div>{app.translator.trans('huseyinfiliz-awards.admin.nominees.name')}</div>
            <div>{app.translator.trans('huseyinfiliz-awards.admin.nominees.votes')}</div>
            <div>{app.translator.trans('huseyinfiliz-awards.admin.nominees.actions')}</div>
          </div>

          {this.nominees.length === 0 ? (
            <div className="EmptyState">
              <i className="fas fa-user" />
              <p>{app.translator.trans('huseyinfiliz-awards.admin.nominees.empty')}</p>
            </div>
          ) : (
            this.nominees.map((nominee, index) => (
              <div className="CardList-item" key={nominee.id()}>
                <div className="CardList-item-cell CardList-item-sort">
                  <Button
                    className="Button Button--icon"
                    icon="fas fa-chevron-up"
                    onclick={() => this.moveNominee(nominee, 'up')}
                    disabled={index === 0}
                    title={app.translator.trans('huseyinfiliz-awards.admin.nominees.move_up') as string}
                  />
                  <Button
                    className="Button Button--icon"
                    icon="fas fa-chevron-down"
                    onclick={() => this.moveNominee(nominee, 'down')}
                    disabled={index === this.nominees.length - 1}
                    title={app.translator.trans('huseyinfiliz-awards.admin.nominees.move_down') as string}
                  />
                </div>
                <div className="CardList-item-cell CardList-item-cell--primary">
                  <div className="CardList-item-nominee">
                    {nominee.imageUrl() ? (
                      <img src={nominee.imageUrl()} alt={nominee.name() as string} className="NomineeImage" />
                    ) : (
                      <div className="NomineeImage NomineeImage--placeholder">
                        <i className="fas fa-image" />
                      </div>
                    )}
                    <div>
                      <div className="CardList-item-name">{nominee.name()}</div>
                      {nominee.description() ? (
                        <div className="CardList-item-meta">{nominee.description()}</div>
                      ) : null}
                    </div>
                  </div>
                </div>
                <div className="CardList-item-cell">
                  <span className="NomineeVotes">{nominee.voteCount?.() || 0}</span>
                  <Button
                    className="Button Button--icon Button--link"
                    icon="fas fa-edit"
                    onclick={() => this.editVotes(nominee)}
                    title={app.translator.trans('huseyinfiliz-awards.admin.nominees.edit_votes') as string}
                  />
                  <Button
                    className="Button Button--icon Button--link"
                    icon="fas fa-undo"
                    onclick={() => this.resetVotes(nominee)}
                    title={app.translator.trans('huseyinfiliz-awards.admin.nominees.reset_votes') as string}
                  />
                </div>
                <div className="CardList-item-actions">
                  <Button className="Button Button--icon" icon="fas fa-edit" onclick={() => this.openModal(nominee)} />
                  <Button
                    className="Button Button--icon Button--danger"
                    icon="fas fa-trash"
                    onclick={() => this.deleteNominee(nominee)}
                  />
                </div>
              </div>
            ))
          )}
        </div>
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

  async resetVotes(nominee: Nominee) {
    if (!confirm(app.translator.trans('huseyinfiliz-awards.admin.nominees.reset_votes_confirm') as string)) {
      return;
    }

    try {
      await app.request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/award-nominees/' + nominee.id() + '/votes',
      });
      this.loadNominees();
    } catch (error) {
      console.error('Failed to reset votes:', error);
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
