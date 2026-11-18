import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import LinkButton from 'flarum/common/components/LinkButton';
export { default as extend } from '../common/extend';
import AwardsPage from './components/AwardsPage';
import ResultsPublishedNotification from './components/ResultsPublishedNotification';

app.initializers.add('huseyinfiliz/awards', () => {
  // Awards routes - discussion-style URLs with optional category deep linking
  app.routes.awards = { path: '/awards', component: AwardsPage };
  app.routes['awards.show'] = { path: '/awards/:id', component: AwardsPage };
  app.routes['awards.category'] = { path: '/awards/:id/:category', component: AwardsPage };

  // Register notification component
  app.notificationComponents.awardsResultsPublished = ResultsPublishedNotification;

  extend(IndexSidebar.prototype, 'navItems', function (items) {
    if (app.forum.attribute('canViewAwards') !== false) {
      const navTitle = app.forum.attribute<string>('awardsNavTitle') || 'Awards';
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
