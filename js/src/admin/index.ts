import app from 'flarum/admin/app';
import extenders from './extend';
import PickemPage from './components/PickemPage';

app.initializers.add('huseyinfiliz/pickem', () => {
  extenders.forEach(extender => extender.extend(app));

  app.extensionData
    .for('huseyinfiliz-pickem')
    .registerPage(PickemPage)
    .registerPermission({
      icon: 'fas fa-trophy',
      label: app.translator.trans('huseyinfiliz-pickem.admin.permissions.manage'),
      permission: 'pickem.manage'
    }, 'moderate')
    .registerPermission({
      icon: 'fas fa-check-circle',
      label: app.translator.trans('huseyinfiliz-pickem.admin.permissions.make_picks'),
      permission: 'pickem.makePicks'
    }, 'start')
    .registerPermission({
      icon: 'fas fa-eye',
      label: app.translator.trans('huseyinfiliz-pickem.admin.permissions.view_page'),
      permission: 'pickem.view',
	  allowGuest: true 
    }, 'view');
});