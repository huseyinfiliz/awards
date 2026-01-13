import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Award from '../../common/models/Award';
import CategoryResultCard from './CategoryResultCard';

export default class ResultsView extends Component {
  view() {
    const award = this.attrs.award as Award;
    const categories = award.categories() || [];

    return (
      <div className="ResultsView">
        <div className="ResultsView-status">
          <span className="TagLabel Label--info">
            {app.translator.trans('huseyinfiliz-awards.forum.page.results_published')}
          </span>
        </div>

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
