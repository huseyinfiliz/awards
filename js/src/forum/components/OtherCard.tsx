import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Category from '../../common/models/Category';
import SuggestionModal from './SuggestionModal';

export default class OtherCard extends Component {
  view() {
    const category = this.attrs.category as Category;

    return (
      <div className="NomineeCard NomineeCard--other">
        <div className="NomineeCard-image">
            <div className="NomineeCard-placeholder">
                <i className="fas fa-plus" />
            </div>
        </div>

        <div className="NomineeCard-content">
            <h3 className="NomineeCard-title">{app.translator.trans('huseyinfiliz-awards.forum.other.suggest')}</h3>

            <Button
                className="Button Button--text"
                onclick={() => app.modal.show(SuggestionModal, { category })}
            >
                {app.translator.trans('huseyinfiliz-awards.forum.other.suggest')}
            </Button>
        </div>
      </div>
    );
  }
}
