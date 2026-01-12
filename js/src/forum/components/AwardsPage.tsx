import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Select from 'flarum/common/components/Select';
import Award from '../../common/models/Award';
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

    const awardOptions: Record<string, string> = {};
    this.awards.forEach((award) => {
      awardOptions[String(award.id())] = `${award.name()} (${award.year()})`;
    });

    return (
      <div className="AwardsPage">
        {this.awards.length > 1 && (
          <div className="AwardsPage-selector container">
            <Select
              value={String(this.selectedAward?.id())}
              options={awardOptions}
              onchange={(value: string) => {
                this.selectedAward = this.awards.find((a) => String(a.id()) === value) || null;
                m.redraw();
              }}
            />
          </div>
        )}

        {this.selectedAward && this.renderAwardView()}
      </div>
    );
  }

  renderAwardView() {
    const award = this.selectedAward!;

    // Show results view for published awards
    if (award.isPublished()) {
      return <ResultsView award={award} />;
    }

    // Show voting view for active or ended awards
    if (award.isActive() || award.hasEnded()) {
      return <VotingView award={award} />;
    }

    // Draft awards shouldn't be visible, but just in case
    return (
      <div className="container">
        <div className="EmptyState">
          <p>{app.translator.trans('huseyinfiliz-awards.forum.error.not_available')}</p>
        </div>
      </div>
    );
  }
}
