import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import AwardModal from './modals/AwardModal';
import Award from '../../common/models/Award';

export default class AwardsTab extends Component {
  loading: boolean = true;
  awards: Award[] = [];

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
    } catch (error) {
      console.error('Failed to load awards:', error);
      this.awards = [];
    }

    this.loading = false;
    m.redraw();
  }

  view() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    return (
      <div className="AwardsTab">
        <div className="AwardsTab-header">
          <Button className="Button Button--primary" icon="fas fa-plus" onclick={() => this.openModal()}>
            {app.translator.trans('huseyinfiliz-awards.admin.awards.create')}
          </Button>
        </div>

        <div className="CardList">
          <div className="CardList-header">
            <div>{app.translator.trans('huseyinfiliz-awards.admin.awards.name')}</div>
            <div>{app.translator.trans('huseyinfiliz-awards.admin.awards.status')}</div>
            <div>{app.translator.trans('huseyinfiliz-awards.admin.awards.categories')} / {app.translator.trans('huseyinfiliz-awards.admin.awards.votes')}</div>
            <div>{app.translator.trans('huseyinfiliz-awards.admin.awards.actions')}</div>
          </div>

          {this.awards.length === 0 ? (
            <div className="EmptyState">
              <i className="fas fa-trophy" />
              <p>{app.translator.trans('huseyinfiliz-awards.admin.awards.empty')}</p>
            </div>
          ) : (
            this.awards.map((award) => (
              <div className="CardList-item" key={award.id()}>
                <div className="CardList-item-cell CardList-item-cell--primary">
                  <div>
                    <div className="CardList-item-name">{award.name()}</div>
                    <div className="CardList-item-meta">
                      <span className="CardList-item-slug">{award.slug()}</span>
                      <span className="CardList-item-year">{award.year()}</span>
                    </div>
                  </div>
                </div>
                <div className="CardList-item-cell">
                  <span className={`StatusBadge StatusBadge--${award.status()}`}>
                    {this.statusLabel(award.status())}
                  </span>
                </div>
                <div className="CardList-item-cell CardList-item-cell--muted">
                  <span title={app.translator.trans('huseyinfiliz-awards.admin.awards.categories') as string}>
                    <i className="fas fa-folder" /> {award.categoryCount?.() || 0}
                  </span>
                  <span title={app.translator.trans('huseyinfiliz-awards.admin.awards.votes') as string}>
                    <i className="fas fa-vote-yea" /> {award.voteCount?.() || 0}
                  </span>
                </div>
                <div className="CardList-item-actions">
                  <Button className="Button Button--icon" icon="fas fa-edit" onclick={() => this.openModal(award)} />
                  {award.status() === 'ended' ? (
                    <Button
                      className="Button Button--icon Button--success"
                      icon="fas fa-bullhorn"
                      onclick={() => this.publishResults(award)}
                      title={app.translator.trans('huseyinfiliz-awards.admin.awards.publish') as string}
                    />
                  ) : null}
                  <Button
                    className="Button Button--icon Button--danger"
                    icon="fas fa-trash"
                    onclick={() => this.deleteAward(award)}
                  />
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    );
  }

  statusLabel(status: string): string {
    const labels: Record<string, string> = {
      draft: app.translator.trans('huseyinfiliz-awards.admin.awards.status_draft') as string,
      active: app.translator.trans('huseyinfiliz-awards.admin.awards.status_active') as string,
      ended: app.translator.trans('huseyinfiliz-awards.admin.awards.status_ended') as string,
      published: app.translator.trans('huseyinfiliz-awards.admin.awards.status_published') as string,
    };
    return labels[status] || status;
  }

  openModal(award?: Award) {
    app.modal.show(AwardModal, {
      award,
      onsubmit: () => this.loadAwards(),
    });
  }

  async publishResults(award: Award) {
    if (!confirm(app.translator.trans('huseyinfiliz-awards.admin.awards.publish_confirm') as string)) {
      return;
    }

    try {
      await app.request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/awards/' + award.id() + '/publish',
      });
      this.loadAwards();
    } catch (error) {
      console.error('Failed to publish results:', error);
    }
  }

  async deleteAward(award: Award) {
    if (!confirm(app.translator.trans('huseyinfiliz-awards.admin.awards.delete_confirm') as string)) {
      return;
    }

    try {
      await award.delete();
      this.loadAwards();
    } catch (error) {
      console.error('Failed to delete award:', error);
    }
  }
}
