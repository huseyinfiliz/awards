import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Category from '../../common/models/Category';
import SuggestionModal from './SuggestionModal';

export default class OtherCard extends Component {
  openModal(category: Category) {
    app.modal.show(SuggestionModal, { category });
  }

  view() {
    const category = this.attrs.category as Category;
    const userPendingCount = category.userPendingSuggestionsCount() || 0;
    const userVoteCount = (category.userVoteIds() || []).length;
    const votesPerCategory = parseInt(app.forum.attribute('awardsVotesPerCategory') || '1', 10);
    const isUnlimited = votesPerCategory === 0;
    const remainingSlots = isUnlimited ? Infinity : votesPerCategory - userVoteCount - userPendingCount;
    const hasNoRemaining = !isUnlimited && remainingSlots <= 0;

    return (
      <div
        className={`NomineeCard NomineeCard--other NomineeCard--clickable ${hasNoRemaining ? 'NomineeCard--exhausted' : ''}`}
        onclick={() => this.openModal(category)}
        role="button"
        tabindex={0}
        onkeydown={(e: KeyboardEvent) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.openModal(category);
          }
        }}
      >
        <div className="NomineeCard-image">
          <div className="NomineeCard-placeholder">
            <i className={hasNoRemaining ? 'fas fa-list' : 'fas fa-plus'} />
          </div>
        </div>

        <div className="NomineeCard-content">
          <h3 className="NomineeCard-title">
            {app.translator.trans('huseyinfiliz-awards.forum.other.suggest')}
          </h3>
          <p className="NomineeCard-description">
            {userPendingCount > 0
              ? app.translator.trans('huseyinfiliz-awards.forum.other.pending_count', { count: userPendingCount })
              : hasNoRemaining
                ? app.translator.trans('huseyinfiliz-awards.forum.other.no_remaining')
                : isUnlimited
                  ? app.translator.trans('huseyinfiliz-awards.forum.other.click_to_suggest')
                  : app.translator.trans('huseyinfiliz-awards.forum.other.remaining', { count: remainingSlots })
            }
          </p>
        </div>
      </div>
    );
  }
}
