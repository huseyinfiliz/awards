import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import LinkButton from 'flarum/common/components/LinkButton';
import NotificationGrid from 'flarum/forum/components/NotificationGrid';
import commonExtenders from '../common/extend';
import PickemPage from './components/PickemPage';
import EventResultNotification from './components/EventResultNotification';

app.initializers.add('huseyinfiliz/pickem', () => {
  commonExtenders.forEach((extender) => extender.extend(app));

  app.routes.pickem = { path: '/pickem', component: PickemPage };

  extend(IndexPage.prototype, 'navItems', function (items) {
    if (app.forum.attribute('pickem.canView')) {
      items.add(
        'pickem',
        LinkButton.component(
          {
            href: app.route('pickem'),
            icon: 'fas fa-trophy',
          },
          app.translator.trans('huseyinfiliz-pickem.lib.nav.pickem')
        ),
        85
      );
    }
  });

  app.notificationComponents.pickem_event_result = EventResultNotification;
});