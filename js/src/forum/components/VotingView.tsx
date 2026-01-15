import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';
import Vote from '../../common/models/Vote';
import CategoryCard from './CategoryCard';
import VotingProgressBar from './VotingProgressBar';

export default class VotingView extends Component {
  getVotedCategoryIds(categories: Category[]): string[] {
    const userVotes = app.store.all<Vote>('award-votes');
    const votedCategoryIds: string[] = [];

    categories.forEach((category) => {
      const categoryId = category.id();
      const hasVote = userVotes.some((v) => {
        const vCategoryId = v.categoryId?.() || v.data?.relationships?.category?.data?.id;
        return String(vCategoryId) === String(categoryId);
      });
      if (hasVote) {
        votedCategoryIds.push(String(categoryId));
      }
    });

    return votedCategoryIds;
  }

  view() {
    const award = this.attrs.award as Award;
    const selectedCategoryId = this.attrs.selectedCategoryId as string | null;
    const allCategories = (award.categories() || []) as Category[];

    // Filter categories if a specific category is selected
    const categories = selectedCategoryId
      ? allCategories.filter((cat) => String(cat.id()) === selectedCategoryId)
      : allCategories;

    // Calculate voting progress
    const votedCategoryIds = this.getVotedCategoryIds(allCategories);
    const totalCategories = allCategories.length;
    const votedCount = votedCategoryIds.length;

    // Get ordered category IDs for navigation
    const categoryIds = allCategories.map((cat) => String(cat.id()));

    return (
      <div className="VotingView">
        <div className="VotingView-content">
          {categories.length === 0 ? (
            <div className="EmptyState">
              <p>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_categories')}</p>
            </div>
          ) : (
            categories.map((category) => <CategoryCard category={category} award={award} />)
          )}
        </div>

        {award.isVotingOpen() && app.session.user ? (
          <VotingProgressBar
            votedCount={votedCount}
            totalCount={totalCategories}
            categoryIds={categoryIds}
            onNavigate={(categoryId: string) => {
              const element = document.getElementById(`category-${categoryId}`);
              if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
              }
            }}
          />
        ) : null}
      </div>
    );
  }
}
