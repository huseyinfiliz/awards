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

    return (
      <div
        className="NomineeCard NomineeCard--other NomineeCard--clickable"
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
            <i className="fas fa-plus" />
          </div>
        </div>

        <div className="NomineeCard-content">
          <h3 className="NomineeCard-title">
            {app.translator.trans('huseyinfiliz-awards.forum.other.suggest')}
          </h3>
          <p className="NomineeCard-description">
            {app.translator.trans('huseyinfiliz-awards.forum.other.click_to_suggest')}
          </p>
        </div>
      </div>
    );
  }
}
