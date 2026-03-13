import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import LinkButton from 'flarum/common/components/LinkButton';
import commonExtenders from '../common/extend';
import AwardsPage from './components/AwardsPage';
import ResultsPublishedNotification from './components/ResultsPublishedNotification';

app.initializers.add('huseyinfiliz/awards', () => {
  commonExtenders.forEach((extender) => extender.extend(app));

  // Awards routes - discussion-style URLs with optional category deep linking
  app.routes.awards = { path: '/awards', component: AwardsPage };
  app.routes['awards.show'] = { path: '/awards/:id', component: AwardsPage };
  app.routes['awards.category'] = { path: '/awards/:id/:category', component: AwardsPage };

  // Register notification component
  app.notificationComponents.awardsResultsPublished = ResultsPublishedNotification;

  extend(IndexPage.prototype, 'navItems', function (items) {
    if (app.forum.attribute('canViewAwards') !== false) {
      const navTitle = app.forum.attribute('awardsNavTitle') || 'Awards';
      const navIcon = app.forum.attribute('awardsNavIcon') || 'fas fa-trophy';
      items.add(
        'awards',
        LinkButton.component(
          {
            href: app.route('awards'),
            icon: navIcon,
          },
          navTitle
        ),
        85
      );
    }
  });
});
