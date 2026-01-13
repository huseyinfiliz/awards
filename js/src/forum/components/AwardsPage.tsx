import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Select from 'flarum/common/components/Select';
import listItems from 'flarum/common/helpers/listItems';
import ItemList from 'flarum/common/utils/ItemList';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';
import VotingView from './VotingView';
import ResultsView from './ResultsView';

export default class AwardsPage extends Page {
  loading: boolean = true;
  awards: Award[] = [];
  selectedAward: Award | null = null;
  selectedCategoryId: string | null = null;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.loadAwards();
  }

  async loadAwards() {
    this.loading = true;
    m.redraw();

    try {
      const awards = await app.store.find<Award[]>('awards', {
        include: 'categories,categories.nominees',
      });
      // Filter to only show active or published awards to regular users
      this.awards = (awards || []).filter(
        (a) => a.isActive() || a.isPublished() || a.hasEnded()
      );

      // Select the first active award, or first published, or first in list
      this.selectedAward =
        this.awards.find((a) => a.isActive()) ||
        this.awards.find((a) => a.isPublished()) ||
        this.awards[0] ||
        null;
    } catch (error) {
      console.error('Failed to load awards:', error);
    }

    this.loading = false;
    m.redraw();
  }

  view() {
    if (this.loading) {
      return (
        <div className="AwardsPage">
          <LoadingIndicator />
        </div>
      );
    }

    if (this.awards.length === 0) {
      return (
        <div className="AwardsPage">
          <div className="container">
            <div className="EmptyState">
              <h2>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_awards')}</h2>
              <p>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_awards_description')}</p>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="AwardsPage">
        <div className="container">
          <div className="sideNavContainer">
            <nav className="AwardsPage-nav sideNav">
              <ul>{listItems(this.sidebarItems().toArray())}</ul>
            </nav>
            <div className="AwardsPage-content sideNavOffset">
              {this.selectedAward && this.renderAwardView()}
            </div>
          </div>
        </div>
      </div>
    );
  }

  sidebarItems(): ItemList<any> {
    const items = new ItemList<any>();

    // Award selector dropdown when there are multiple awards
    if (this.awards.length > 1) {
      const awardOptions: Record<string, string> = {};
      this.awards.forEach((award) => {
        awardOptions[String(award.id())] = `${award.name()} (${award.year()})`;
      });

      items.add(
        'awardSelector',
        <li className="Nav-item AwardsPage-selectorItem">
          <Select
            value={String(this.selectedAward?.id())}
            options={awardOptions}
            onchange={(value: string) => {
              this.selectedAward = this.awards.find((a) => String(a.id()) === value) || null;
              this.selectedCategoryId = null;
              m.redraw();
            }}
          />
        </li>,
        100
      );
    }

    // Show award info
    if (this.selectedAward) {
      items.add(
        'awardInfo',
        <li className="Nav-item AwardsPage-awardInfo">
          <div className="AwardsPage-awardName">
            <strong>{this.selectedAward.name()}</strong>
            <span className="AwardsPage-awardYear">{this.selectedAward.year()}</span>
          </div>
        </li>,
        90
      );

      // Categories as nav items
      const categories = (this.selectedAward.categories?.() || []) as Category[];
      if (categories.length > 0) {
        items.add(
          'categoriesTitle',
          <li className="Nav-item Nav-header">
            <span>{app.translator.trans('huseyinfiliz-awards.forum.nav.categories')}</span>
          </li>,
          80
        );

        items.add(
          'allCategories',
          <li className="Nav-item">
            <a
              href="#"
              className={!this.selectedCategoryId ? 'active' : ''}
              onclick={(e: Event) => {
                e.preventDefault();
                this.selectedCategoryId = null;
                m.redraw();
              }}
            >
              <i className="fas fa-th-list"></i>
              {app.translator.trans('huseyinfiliz-awards.forum.nav.all_categories')}
            </a>
          </li>,
          70
        );

        categories.forEach((category, index) => {
          items.add(
            `category-${category.id()}`,
            <li className="Nav-item">
              <a
                href="#"
                className={this.selectedCategoryId === String(category.id()) ? 'active' : ''}
                onclick={(e: Event) => {
                  e.preventDefault();
                  this.selectedCategoryId = String(category.id());
                  m.redraw();
                }}
              >
                {category.name()}
              </a>
            </li>,
            60 - index
          );
        });
      }
    }

    return items;
  }

  renderAwardView() {
    const award = this.selectedAward!;

    // Show results view for published awards
    if (award.isPublished()) {
      return <ResultsView award={award} selectedCategoryId={this.selectedCategoryId} />;
    }

    // Show voting view for active or ended awards
    if (award.isActive() || award.hasEnded()) {
      return <VotingView award={award} selectedCategoryId={this.selectedCategoryId} />;
    }

    // Draft awards shouldn't be visible, but just in case
    return (
      <div className="EmptyState">
        <p>{app.translator.trans('huseyinfiliz-awards.forum.error.not_available')}</p>
      </div>
    );
  }
}
