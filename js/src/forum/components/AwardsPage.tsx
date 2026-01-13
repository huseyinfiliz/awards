import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Select from 'flarum/common/components/Select';
import IndexPage from 'flarum/forum/components/IndexPage';
import listItems from 'flarum/common/helpers/listItems';
import extractText from 'flarum/common/utils/extractText';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';
import Vote from '../../common/models/Vote';
import VotingView from './VotingView';
import ResultsView from './ResultsView';

export default class AwardsPage extends Page {
  loading: boolean = true;
  awards: Award[] = [];
  selectedAward: Award | null = null;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.loadAwards();
  }

  oncreate(vnode: any) {
    super.oncreate(vnode);
    const navTitle = app.forum.attribute('awardsNavTitle') || 'Awards';
    app.setTitle(extractText(navTitle));
  }

  async loadAwards() {
    this.loading = true;
    m.redraw();

    try {
      const awards = await app.store.find<Award[]>('awards', {
        include: 'categories,categories.nominees',
      });
      this.awards = (awards || []).filter((a) => a.isActive() || a.isPublished() || a.hasEnded());

      this.selectedAward =
        this.awards.find((a) => a.isActive()) || this.awards.find((a) => a.isPublished()) || this.awards[0] || null;

      if (app.session.user) {
        await this.loadUserVotes();
      }
    } catch (error) {
      console.error('Failed to load awards:', error);
    }

    this.loading = false;
    m.redraw();
  }

  async loadUserVotes() {
    try {
      await app.store.find<Vote[]>('award-votes');
    } catch (error) {
      console.error('Failed to load user votes:', error);
    }
  }

  view() {
    const navTitle = app.forum.attribute('awardsNavTitle') || 'Awards';

    return (
      <div className="IndexPage huseyinfiliz-awards">
        {this.hero(navTitle)}

        <div className="container">
          <div className="sideNavContainer">
            <nav className="IndexPage-nav sideNav">
              <ul>{listItems(IndexPage.prototype.sidebarItems().toArray())}</ul>
            </nav>

            <div className="IndexPage-results sideNavOffset">
              {this.loading ? <LoadingIndicator /> : this.content()}
            </div>
          </div>
        </div>
      </div>
    );
  }

  hero(navTitle: string) {
    return (
      <header className="Hero AwardsHero">
        <div className="container">
          <div className="containerNarrow">
            <h1 className="Hero-title">
              <i className="icon fas fa-trophy" /> {navTitle}
            </h1>
            {this.selectedAward ? (
              <div className="Hero-subtitle">{this.selectedAward.description()}</div>
            ) : null}
          </div>
        </div>
      </header>
    );
  }

  content() {
    if (this.awards.length === 0) {
      return (
        <div className="EmptyState">
          <i className="fas fa-trophy" />
          <h2>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_awards')}</h2>
          <p>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_awards_description')}</p>
        </div>
      );
    }

    return (
      <div className="AwardsPage-content">
        {this.awards.length > 1 ? this.renderAwardSelector() : null}
        {this.selectedAward ? this.renderCategoryNav() : null}
        {this.selectedAward ? this.renderAwardView() : null}
      </div>
    );
  }

  renderAwardSelector() {
    const awardOptions: Record<string, string> = {};
    this.awards.forEach((award) => {
      awardOptions[String(award.id())] = `${award.name()} (${award.year()})`;
    });

    return (
      <div className="AwardsPage-selector">
        <Select
          value={String(this.selectedAward?.id())}
          options={awardOptions}
          onchange={(value: string) => {
            this.selectedAward = this.awards.find((a) => String(a.id()) === value) || null;
            m.redraw();
          }}
        />
      </div>
    );
  }

  renderCategoryNav() {
    const categories = (this.selectedAward?.categories?.() || []) as Category[];
    if (categories.length === 0) return null;

    return (
      <div className="AwardsPage-categoryNav">
        {categories.map((category) => (
          <button
            key={category.id()}
            className="Button Button--text AwardsPage-categoryLink"
            onclick={() => this.scrollToCategory(category.id())}
          >
            {category.name()}
          </button>
        ))}
      </div>
    );
  }

  scrollToCategory(categoryId: string | number | undefined) {
    if (!categoryId) return;
    const element = document.getElementById(`category-${categoryId}`);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  renderAwardView() {
    const award = this.selectedAward!;

    if (award.canViewResults()) {
      return <ResultsView award={award} />;
    }

    if (award.isActive() || award.hasEnded()) {
      return <VotingView award={award} />;
    }

    return (
      <div className="EmptyState">
        <p>{app.translator.trans('huseyinfiliz-awards.forum.error.not_available')}</p>
      </div>
    );
  }
}
