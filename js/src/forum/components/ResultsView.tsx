import Component from 'flarum/common/Component';
import Award from '../../common/models/Award';
import CategoryResultCard from './CategoryResultCard';

export default class ResultsView extends Component {
  view() {
    const award = this.attrs.award as Award;
    const categories = award.categories() || [];

    return (
      <div className="ResultsView">
        <div className="ResultsView-header Hero">
           <div className="Hero-content">
             <h1>{award.name()}</h1>
             <p className="lead">{award.description()}</p>
             <div className="ResultsView-meta">
                 <span className="TagLabel Label--info">
                     {app.translator.trans('huseyinfiliz-awards.forum.page.voting_ended', { time: new Date(award.endsAt()!).toLocaleDateString() })}
                 </span>
             </div>
           </div>
        </div>

        <div className="ResultsView-content">
             {categories.length === 0 ? (
                 <div className="EmptyState">
                    <p>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_categories')}</p>
                 </div>
            ) : (
                categories.map(category => (
                    <CategoryResultCard category={category} award={award} />
                ))
            )}
        </div>
      </div>
    );
  }
}
