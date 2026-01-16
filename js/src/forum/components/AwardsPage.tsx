import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Select from 'flarum/common/components/Select';
import Button from 'flarum/common/components/Button';
import IndexPage from 'flarum/forum/components/IndexPage';
import listItems from 'flarum/common/helpers/listItems';
import extractText from 'flarum/common/utils/extractText';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';
import Vote from '../../common/models/Vote';
import VotingView from './VotingView';
import ResultsView from './ResultsView';
import MyVotesView from './MyVotesView';

type ViewType = 'categories' | 'my_votes' | 'results';

export default class AwardsPage extends Page {
  loading: boolean = true;
  awards: Award[] = [];
  selectedAward: Award | null = null;
  selectedCategoryId: string | null = null; // null means "All Categories"
  currentView: ViewType = 'categories';
  countdownInterval: number | null = null;
  countdownText: string = '';
  refreshInterval: number | null = null;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.loadAwards();
  }

  oncreate(vnode: any) {
    super.oncreate(vnode);
    const navTitle = app.forum.attribute('awardsNavTitle') || 'Awards';
    app.setTitle(extractText(navTitle));
    this.startCountdown();
  }

  onremove(vnode: any) {
    super.onremove(vnode);
    if (this.countdownInterval) {
      clearInterval(this.countdownInterval);
    }
    this.stopAutoRefresh();
  }

  startCountdown() {
    this.updateCountdown();
    this.countdownInterval = window.setInterval(() => {
      this.updateCountdown();
      m.redraw();
    }, 1000);
  }

  updateCountdown() {
    if (!this.selectedAward) {
      this.countdownText = '';
      return;
    }

    const endsAt = this.selectedAward.endsAt();
    if (!endsAt) {
      this.countdownText = '';
      return;
    }

    const now = new Date();
    const diff = endsAt.getTime() - now.getTime();

    if (diff <= 0) {
      this.countdownText = app.translator.trans('huseyinfiliz-awards.forum.hero.voting_ended') as string;
      return;
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    let timeStr = '';
    if (days > 0) timeStr += `${days}d `;
    if (hours > 0 || days > 0) timeStr += `${hours}h `;
    if (minutes > 0 || hours > 0 || days > 0) timeStr += `${minutes}m `;
    timeStr += `${seconds}s`;

    this.countdownText = app.translator.trans('huseyinfiliz-awards.forum.hero.countdown', { time: timeStr }) as string;
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

      // Set default view based on award status
      if (this.selectedAward?.canViewResults()) {
        this.currentView = 'results';
      } else {
        this.currentView = 'categories';
      }

      if (app.session.user) {
        await this.loadUserVotes();
      }
    } catch (error) {
      console.error('Failed to load awards:', error);
    }

    this.loading = false;
    this.startAutoRefresh();
    m.redraw();
  }

  startAutoRefresh() {
    if (!this.selectedAward) return;

    // Only refresh if voting is open and live votes are shown
    if (this.selectedAward.isVotingOpen() && this.selectedAward.showLiveVotes()) {
      this.stopAutoRefresh(); // Clear any existing interval
      this.refreshInterval = window.setInterval(() => {
        this.refreshVoteCounts();
      }, 60000); // 60 seconds
    }
  }

  stopAutoRefresh() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
      this.refreshInterval = null;
    }
  }

  async refreshVoteCounts() {
    if (!this.selectedAward) return;

    try {
      await app.store.find('awards', this.selectedAward.id(), {
        include: 'categories,categories.nominees',
      });
      m.redraw();
    } catch (error) {
      console.error('Failed to refresh vote counts:', error);
    }
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
    const navIcon = app.forum.attribute('awardsNavIcon') || 'fas fa-trophy';

    return (
      <div className="IndexPage huseyinfiliz-awards">
        {this.hero(navTitle, navIcon)}

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

  hero(navTitle: string, navIcon: string) {
    const imageUrl = this.selectedAward?.imageUrl?.();
    const hasImage = imageUrl && imageUrl.length > 0;

    return (
      <header
        className={`Hero AwardsHero ${hasImage ? 'AwardsHero--withImage' : ''}`}
        style={hasImage ? { backgroundImage: `url(${imageUrl})` } : undefined}
      >
        {hasImage ? <div className="AwardsHero-overlay" /> : null}
        <div className="container">
          <div className="containerNarrow">
            <h1 className="Hero-title">
              <i className={`icon ${navIcon}`} /> {navTitle}
            </h1>
            {this.selectedAward ? (
              <div className="Hero-subtitle">{this.selectedAward.description()}</div>
            ) : null}
            {this.selectedAward && this.selectedAward.isVotingOpen() && this.countdownText ? (
              <div className="AwardsHero-countdown">
                <i className="fas fa-clock" /> {this.countdownText}
              </div>
            ) : null}
            {this.selectedAward && !this.selectedAward.isVotingOpen() && this.selectedAward.hasEnded() && !this.selectedAward.isPublished() ? (
              <div className="AwardsHero-countdown AwardsHero-countdown--ended">
                {app.translator.trans('huseyinfiliz-awards.forum.hero.voting_ended')}
              </div>
            ) : null}
            {this.selectedAward && this.selectedAward.isPublished() ? (
              <div className="AwardsHero-countdown AwardsHero-countdown--published">
                {app.translator.trans('huseyinfiliz-awards.forum.hero.results_published')}
              </div>
            ) : null}
          </div>
        </div>
      </header>
    );
  }

  content() {
    const navIcon = app.forum.attribute('awardsNavIcon') || 'fas fa-trophy';

    if (this.awards.length === 0) {
      return (
        <div className="EmptyState">
          <i className={navIcon} />
          <h2>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_awards')}</h2>
          <p>{app.translator.trans('huseyinfiliz-awards.forum.empty.no_awards_description')}</p>
        </div>
      );
    }

    return (
      <div className="AwardsPage-content">
        {this.renderFilterBar()}
        {this.renderAwardView()}
      </div>
    );
  }

  renderFilterBar() {
    const categories = (this.selectedAward?.categories?.() || []) as Category[];
    const showResultsTab = this.selectedAward?.isPublished();
    const showMyVotesTab = app.session.user;

    // Build category filter options - build categories first, then prepend 'all' to ensure order
    const categoryOnlyOptions: Record<string, string> = {};
    categories.forEach((cat) => {
      categoryOnlyOptions[String(cat.id())] = cat.name() as string;
    });
    const categoryOptions: Record<string, string> = {
      'all': app.translator.trans('huseyinfiliz-awards.forum.nav.all_categories') as string,
      ...categoryOnlyOptions,
    };

    return (
      <div className="AwardsPage-filterBar">
        <div className="AwardsPage-filterBar-left">
          {/* Award selector (if multiple awards) */}
          {this.awards.length > 1 ? this.renderAwardSelector() : null}

          {/* Category filter dropdown */}
          {categories.length > 0 && this.currentView !== 'my_votes' ? (
            <div className="AwardsPage-categoryFilter">
              <Select
                value={this.selectedCategoryId || 'all'}
                options={categoryOptions}
                onchange={(value: string) => {
                  this.selectedCategoryId = value === 'all' ? null : value;
                  m.redraw();
                }}
              />
            </div>
          ) : null}
        </div>

        <div className="AwardsPage-filterBar-right">
          {/* Tab buttons */}
          <div className="AwardsPage-tabs">
            <Button
              className={`Button ${this.currentView === 'categories' || this.currentView === 'results' && !showResultsTab ? 'Button--primary' : ''}`}
              onclick={() => {
                this.currentView = showResultsTab ? 'results' : 'categories';
                m.redraw();
              }}
            >
              {showResultsTab
                ? app.translator.trans('huseyinfiliz-awards.forum.tabs.results')
                : app.translator.trans('huseyinfiliz-awards.forum.tabs.categories')
              }
            </Button>

            {showMyVotesTab ? (
              <Button
                className={`Button ${this.currentView === 'my_votes' ? 'Button--primary' : ''}`}
                onclick={() => {
                  this.currentView = 'my_votes';
                  m.redraw();
                }}
                icon="fas fa-clipboard-list"
              >
                {app.translator.trans('huseyinfiliz-awards.forum.tabs.my_votes')}
              </Button>
            ) : null}
          </div>
        </div>
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
            this.selectedCategoryId = null;
            // Update view based on new award status
            if (this.selectedAward?.canViewResults()) {
              this.currentView = 'results';
            } else {
              this.currentView = 'categories';
            }
            m.redraw();
          }}
        />
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

    // My Votes view
    if (this.currentView === 'my_votes' && app.session.user) {
      return (
        <MyVotesView
          award={award}
          onNavigateToCategory={(categoryId: string) => {
            this.currentView = 'categories';
            this.selectedCategoryId = categoryId;
            m.redraw();
            // Delay scroll to allow DOM update
            setTimeout(() => this.scrollToCategory(categoryId), 100);
          }}
        />
      );
    }

    // Results view
    if (award.canViewResults()) {
      return <ResultsView award={award} selectedCategoryId={this.selectedCategoryId} />;
    }

    // Voting view
    if (award.isActive() || award.hasEnded()) {
      return <VotingView award={award} selectedCategoryId={this.selectedCategoryId} />;
    }

    return (
      <div className="EmptyState">
        <p>{app.translator.trans('huseyinfiliz-awards.forum.error.not_available')}</p>
      </div>
    );
  }
}
