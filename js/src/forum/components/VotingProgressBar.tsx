import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';

export default class VotingProgressBar extends Component {
  currentCategoryIndex: number = 0;
  showConfetti: boolean = false;
  confettiShown: boolean = false;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.checkCompletion();
  }

  onupdate(vnode: any) {
    super.onupdate(vnode);
    this.checkCompletion();
  }

  checkCompletion() {
    const votedCount = this.attrs.votedCount as number;
    const totalCount = this.attrs.totalCount as number;

    if (votedCount === totalCount && totalCount > 0 && !this.confettiShown) {
      this.showConfetti = true;
      this.confettiShown = true;

      // Hide confetti after animation
      setTimeout(() => {
        this.showConfetti = false;
        m.redraw();
      }, 4000);
    }
  }

  view() {
    const votedCount = this.attrs.votedCount as number;
    const totalCount = this.attrs.totalCount as number;
    const categoryIds = this.attrs.categoryIds as string[];
    const onNavigate = this.attrs.onNavigate as (categoryId: string) => void;
    const selectedCategoryId = this.attrs.selectedCategoryId as string | null;
    const onCategoryChange = this.attrs.onCategoryChange as ((categoryId: string | null) => void) | undefined;

    const isComplete = votedCount === totalCount && totalCount > 0;
    const progressPercent = totalCount > 0 ? (votedCount / totalCount) * 100 : 0;
    const isFilterMode = !!selectedCategoryId && !!onCategoryChange;

    return (
      <div className={`VotingProgressBar ${isComplete ? 'VotingProgressBar--complete' : ''} ${this.showConfetti ? 'VotingProgressBar--confetti' : ''}`}>
        {this.showConfetti ? this.renderConfetti() : null}

        <div className="VotingProgressBar-content">
          <div className="VotingProgressBar-progress">
            <div className="VotingProgressBar-progressTrack">
              <div
                className="VotingProgressBar-progressFill"
                style={{ width: `${progressPercent}%` }}
              />
            </div>
            <span className="VotingProgressBar-text">
              {isComplete
                ? app.translator.trans('huseyinfiliz-awards.forum.progress.complete')
                : app.translator.trans('huseyinfiliz-awards.forum.progress.voted', {
                    count: votedCount,
                    total: totalCount,
                  })
              }
            </span>
          </div>

          <div className="VotingProgressBar-nav">
            <Button
              className="Button Button--icon"
              icon="fas fa-chevron-left"
              onclick={() => this.navigate(-1, categoryIds, onNavigate, selectedCategoryId, onCategoryChange)}
              disabled={categoryIds.length === 0}
              title={app.translator.trans('huseyinfiliz-awards.forum.progress.prev') as string}
            >
              {app.translator.trans('huseyinfiliz-awards.forum.progress.prev')}
            </Button>
            <Button
              className="Button Button--icon"
              icon="fas fa-chevron-right"
              onclick={() => this.navigate(1, categoryIds, onNavigate, selectedCategoryId, onCategoryChange)}
              disabled={categoryIds.length === 0}
              title={app.translator.trans('huseyinfiliz-awards.forum.progress.next') as string}
            >
              {app.translator.trans('huseyinfiliz-awards.forum.progress.next')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  navigate(
    direction: number,
    categoryIds: string[],
    onNavigate: (categoryId: string) => void,
    selectedCategoryId?: string | null,
    onCategoryChange?: (categoryId: string | null) => void
  ) {
    if (categoryIds.length === 0) return;

    // Filter navigation mode: navigate between category filters
    if (selectedCategoryId && onCategoryChange) {
      const currentIndex = categoryIds.indexOf(selectedCategoryId);
      if (currentIndex === -1) return;

      let newIndex = currentIndex + direction;
      if (newIndex < 0) newIndex = categoryIds.length - 1;
      if (newIndex >= categoryIds.length) newIndex = 0;

      onCategoryChange(categoryIds[newIndex]);
      return;
    }

    // Scroll mode: scroll between categories on the page
    let visibleCategoryId: string | null = null;
    for (const id of categoryIds) {
      const element = document.getElementById(`category-${id}`);
      if (element) {
        const rect = element.getBoundingClientRect();
        if (rect.top >= 0 && rect.top < window.innerHeight / 2) {
          visibleCategoryId = id;
          break;
        }
      }
    }

    let currentIndex = visibleCategoryId ? categoryIds.indexOf(visibleCategoryId) : 0;
    if (currentIndex === -1) currentIndex = 0;

    let newIndex = currentIndex + direction;
    if (newIndex < 0) newIndex = categoryIds.length - 1;
    if (newIndex >= categoryIds.length) newIndex = 0;

    onNavigate(categoryIds[newIndex]);
  }

  renderConfetti() {
    const colors = ['#ffd700', '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#ffeaa7', '#dfe6e9'];
    const pieces: any[] = [];

    for (let i = 0; i < 30; i++) {
      const color = colors[Math.floor(Math.random() * colors.length)];
      const left = Math.random() * 100;
      const delay = Math.random() * 0.5;
      const size = 6 + Math.random() * 6;
      const rotation = Math.random() * 360;

      pieces.push(
        <div
          key={i}
          className="VotingProgressBar-confettiPiece"
          style={{
            left: `${left}%`,
            animationDelay: `${delay}s`,
            backgroundColor: color,
            width: `${size}px`,
            height: `${size}px`,
            transform: `rotate(${rotation}deg)`,
          }}
        />
      );
    }

    return <div className="VotingProgressBar-confettiContainer">{pieces}</div>;
  }
}
