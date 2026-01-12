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

        <table className="AwardsTab-table Table">
          <thead>
            <tr>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.awards.name')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.awards.year')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.awards.status')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.awards.categories')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.awards.votes')}</th>
              <th>{app.translator.trans('huseyinfiliz-awards.admin.awards.actions')}</th>
            </tr>
          </thead>
          <tbody>
            {this.awards.length === 0 ? (
              <tr>
                <td colSpan={6} className="AwardsTab-empty">
                  {app.translator.trans('huseyinfiliz-awards.admin.awards.empty')}
                </td>
              </tr>
            ) : (
              this.awards.map((award) => (
                <tr key={award.id()}>
                  <td>
                    <strong>{award.name()}</strong>
                    <div className="helpText">{award.slug()}</div>
                  </td>
                  <td>{award.year()}</td>
                  <td>
                    <span className={'AwardStatus AwardStatus--' + award.status()}>{this.statusLabel(award.status())}</span>
                  </td>
                  <td>{award.categoryCount?.() || 0}</td>
                  <td>{award.voteCount?.() || 0}</td>
                  <td className="AwardsTab-actions">
                    <Button className="Button Button--icon" icon="fas fa-edit" onclick={() => this.openModal(award)} />
                    {award.status() === 'ended' && (
                      <Button
                        className="Button Button--icon Button--primary"
                        icon="fas fa-bullhorn"
                        onclick={() => this.publishResults(award)}
                        title={app.translator.trans('huseyinfiliz-awards.admin.awards.publish')}
                      />
                    )}
                    <Button
                      className="Button Button--icon Button--danger"
                      icon="fas fa-trash"
                      onclick={() => this.deleteAward(award)}
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
