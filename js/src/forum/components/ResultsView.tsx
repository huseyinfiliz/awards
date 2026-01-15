import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';
import CategoryResultCard from './CategoryResultCard';
import PredictionSummary from './PredictionSummary';

export default class ResultsView extends Component {
  view() {
    const award = this.attrs.award as Award;
    const selectedCategoryId = this.attrs.selectedCategoryId as string | null;
    const allCategories = (award.categories() || []) as Category[];

    // Filter categories if a specific category is selected
    const categories = selectedCategoryId
      ? allCategories.filter((cat) => String(cat.id()) === selectedCategoryId)
      : allCategories;

    return (
      <div className="ResultsView">
        {/* Prediction Summary - only shown when viewing all categories */}
        {app.session.user && !selectedCategoryId ? (
          <PredictionSummary award={award} categories={allCategories} />
        ) : null}

        <div className="ResultsView-content">
          {categories.length === 0 ? (
            <div className="EmptyState">
              <p>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_categories')}</p>
            </div>
          ) : (
            categories.map((category) => <CategoryResultCard category={category} award={award} />)
          )}
        </div>
      </div>
    );
  }
}
