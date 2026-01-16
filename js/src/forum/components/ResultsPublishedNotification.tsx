import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';

export default class ResultsPublishedNotification extends Notification {
  icon() {
    return 'fas fa-trophy';
  }

  href() {
    const content = this.attrs.notification.content() || {};
    const awardId = content.awardId;
    const awardSlug = content.awardSlug || 'award';

    if (awardId) {
      return app.route('awards.show', { id: `${awardId}-${awardSlug}` });
    }

    return app.route('awards');
  }

  content() {
    const content = this.attrs.notification.content() || {};
    const awardName = content.awardName || 'Awards';

    return app.translator.trans('huseyinfiliz-awards.forum.notification.results_published', {
      awardName,
    });
  }

  excerpt() {
    return null;
  }
}
