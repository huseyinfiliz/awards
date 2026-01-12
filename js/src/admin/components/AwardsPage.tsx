import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import AwardsTab from './AwardsTab';
import CategoriesTab from './CategoriesTab';
import NomineesTab from './NomineesTab';
import SuggestionsTab from './SuggestionsTab';
import SettingsTab from './SettingsTab';

export default class AwardsPage extends ExtensionPage {
  currentTab: string = 'awards';

  oninit(vnode: any) {
    super.oninit(vnode);
    this.currentTab = 'awards';
  }

  content() {
    return (
      <div className="AwardsPage">
        <div className="AwardsPage-header">
          <div className="container">
            <h2>{app.translator.trans('huseyinfiliz-awards.admin.title')}</h2>
          </div>
        </div>

        <div className="AwardsPage-nav">
          <div className="container">
            <nav className="AwardsPage-tabs">
              {this.tabButton('awards', 'fas fa-trophy', 'huseyinfiliz-awards.admin.tabs.awards')}
              {this.tabButton('categories', 'fas fa-folder', 'huseyinfiliz-awards.admin.tabs.categories')}
              {this.tabButton('nominees', 'fas fa-user-tie', 'huseyinfiliz-awards.admin.tabs.nominees')}
              {this.tabButton('suggestions', 'fas fa-lightbulb', 'huseyinfiliz-awards.admin.tabs.suggestions')}
              {this.tabButton('settings', 'fas fa-cog', 'huseyinfiliz-awards.admin.tabs.settings')}
            </nav>
          </div>
        </div>

        <div className="AwardsPage-content">
          <div className="container">
            {this.currentTab === 'awards' && <AwardsTab />}
            {this.currentTab === 'categories' && <CategoriesTab />}
            {this.currentTab === 'nominees' && <NomineesTab />}
            {this.currentTab === 'suggestions' && <SuggestionsTab />}
            {this.currentTab === 'settings' && <SettingsTab />}
          </div>
        </div>
      </div>
    );
  }

  tabButton(tab: string, icon: string, translationKey: string) {
    return (
      <Button
        className={'Button ' + (this.currentTab === tab ? 'Button--primary' : '')}
        icon={icon}
        onclick={() => {
          this.currentTab = tab;
          m.redraw();
        }}
      >
        {app.translator.trans(translationKey)}
      </Button>
    );
  }
}
