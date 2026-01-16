import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';

export default class SettingsTab extends Component {
  navTitle: Stream<string>;
  navIcon: Stream<string>;
  votesPerCategory: Stream<number>;
  loading: boolean = false;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.navTitle = Stream(app.data.settings['huseyinfiliz-awards.nav_title'] || 'Awards');
    this.navIcon = Stream(app.data.settings['huseyinfiliz-awards.nav_icon'] || 'fas fa-trophy');
    this.votesPerCategory = Stream(parseInt(app.data.settings['huseyinfiliz-awards.votes_per_category'] || '1'));
  }

  view() {
    return (
      <div className="SettingsTab">
        <div className="Form-group">
          <h3>
            <i className="fas fa-cogs" />
            {app.translator.trans('huseyinfiliz-awards.admin.tabs.settings')}
          </h3>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.settings.nav_title')}</label>
            <input className="FormControl" bidi={this.navTitle} />
            <div className="helpText">{app.translator.trans('huseyinfiliz-awards.admin.settings.nav_title_help')}</div>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.settings.nav_icon')}</label>
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
              <input className="FormControl" bidi={this.navIcon} style={{ flex: 1 }} />
              <span style={{ fontSize: '20px' }}>
                <i className={this.navIcon()} />
              </span>
            </div>
            <div className="helpText">{app.translator.trans('huseyinfiliz-awards.admin.settings.nav_icon_help')}</div>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('huseyinfiliz-awards.admin.settings.votes_per_category')}</label>
            <input className="FormControl" type="number" min="0" bidi={this.votesPerCategory} />
            <div className="helpText">{app.translator.trans('huseyinfiliz-awards.admin.settings.votes_per_category_help')}</div>
          </div>

          <div className="Form-group">
            <Button className="Button Button--primary" loading={this.loading} onclick={this.saveSettings.bind(this)}>
              {app.translator.trans('huseyinfiliz-awards.lib.save')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  saveSettings() {
    this.loading = true;
    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/settings',
        body: {
          'huseyinfiliz-awards.nav_title': this.navTitle(),
          'huseyinfiliz-awards.nav_icon': this.navIcon(),
          'huseyinfiliz-awards.votes_per_category': this.votesPerCategory(),
        },
      })
      .then(() => {
        this.loading = false;
        m.redraw();
        app.alerts.show({ type: 'success' }, app.translator.trans('huseyinfiliz-awards.lib.success_message'));
      });
  }
}
