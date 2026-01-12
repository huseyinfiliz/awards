import app from 'flarum/admin/app';
import extenders from './extend';
import AwardsPage from './components/AwardsPage';

app.initializers.add('huseyinfiliz/awards', () => {
  extenders.forEach((extender) => extender.extend(app));

  app.extensionData
    .for('huseyinfiliz-awards')
    .registerPage(AwardsPage)
    .registerPermission(
      {
        icon: 'fas fa-cog',
        label: app.translator.trans('huseyinfiliz-awards.admin.permissions.manage'),
        permission: 'awards.manage',
      },
      'moderate'
    )
    .registerPermission(
      {
        icon: 'fas fa-vote-yea',
        label: app.translator.trans('huseyinfiliz-awards.admin.permissions.vote'),
        permission: 'awards.vote',
      },
      'reply'
    )
    .registerPermission(
      {
        icon: 'fas fa-trophy',
        label: app.translator.trans('huseyinfiliz-awards.admin.permissions.view'),
        permission: 'awards.view',
        allowGuest: true,
      },
      'view'
    )
    .registerPermission(
      {
        icon: 'fas fa-chart-bar',
        label: app.translator.trans('huseyinfiliz-awards.admin.permissions.view_results'),
        permission: 'awards.viewResults',
      },
      'moderate'
    );
});
