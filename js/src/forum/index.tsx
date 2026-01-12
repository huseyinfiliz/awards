import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import LinkButton from 'flarum/common/components/LinkButton';
import commonExtenders from '../common/extend';
import AwardsPage from './components/AwardsPage';

app.initializers.add('huseyinfiliz/awards', () => {
  commonExtenders.forEach((extender) => extender.extend(app));

  app.routes.awards = { path: '/awards', component: AwardsPage };

  extend(IndexPage.prototype, 'navItems', function (items) {
    if (app.forum.attribute('awards.canView') !== false) {
      const navTitle = app.forum.attribute('huseyinfiliz-awards.nav_title') || 'Awards';
      items.add(
        'awards',
        LinkButton.component(
          {
            href: app.route('awards'),
            icon: 'fas fa-trophy',
          },
          navTitle
        ),
        85
      );
    }
  });
});
