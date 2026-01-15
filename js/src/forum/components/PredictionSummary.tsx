import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Award from '../../common/models/Award';
import Category from '../../common/models/Category';
import Nominee from '../../common/models/Nominee';
import Vote from '../../common/models/Vote';

interface PredictionResult {
  category: Category;
  userVotedNominee: Nominee | null;
  winner: Nominee | null;
  isCorrect: boolean;
}

export default class PredictionSummary extends Component {
  showDetails: boolean = false;

  getPredictionResults(award: Award, categories: Category[]): PredictionResult[] {
    const userVotes = app.store.all<Vote>('award-votes');
    const results: PredictionResult[] = [];

    categories.forEach((category) => {
      const categoryId = category.id();
      const nominees = (category.nominees() || []) as Nominee[];

      // Find user's vote for this category
      const userVote = userVotes.find((v) => {
        const vCategoryId = v.categoryId?.() || v.data?.relationships?.category?.data?.id;
        return String(vCategoryId) === String(categoryId);
      });

      let userVotedNominee: Nominee | null = null;
      if (userVote) {
        const vNomineeId = userVote.nomineeId?.() || userVote.data?.relationships?.nominee?.data?.id;
        userVotedNominee = nominees.find((n) => String(n.id()) === String(vNomineeId)) || null;
      }

      // Find winner (nominee with highest vote count)
      const winner = nominees.length > 0
        ? nominees.reduce((a, b) => ((b.voteCount() || 0) > (a.voteCount() || 0) ? b : a))
        : null;

      // Check if user predicted correctly
      const isCorrect = userVotedNominee && winner && String(userVotedNominee.id()) === String(winner.id());

      results.push({
        category,
        userVotedNominee,
        winner,
        isCorrect: !!isCorrect,
      });
    });

    return results;
  }

  view() {
    const award = this.attrs.award as Award;
    const categories = this.attrs.categories as Category[];

    const results = this.getPredictionResults(award, categories);
    const votedResults = results.filter((r) => r.userVotedNominee !== null);

    // Only show if user has voted in at least one category
    if (votedResults.length === 0) {
      return null;
    }

    const correctCount = votedResults.filter((r) => r.isCorrect).length;
    const totalVoted = votedResults.length;
    const progressPercent = totalVoted > 0 ? (correctCount / totalVoted) * 100 : 0;

    const correctPredictions = votedResults.filter((r) => r.isCorrect);
    const wrongPredictions = votedResults.filter((r) => !r.isCorrect);

    return (
      <div className="PredictionSummary">
        <div className="PredictionSummary-header">
          <i className="fas fa-bullseye" />
          <h3>{app.translator.trans('huseyinfiliz-awards.forum.prediction.title')}</h3>
        </div>

        <div className="PredictionSummary-content">
          <div className="PredictionSummary-progress">
            <div className="PredictionSummary-progressTrack">
              <div
                className="PredictionSummary-progressFill"
                style={{ width: `${progressPercent}%` }}
              />
            </div>
            <span className="PredictionSummary-score">
              {app.translator.trans('huseyinfiliz-awards.forum.prediction.score', {
                correct: correctCount,
                total: totalVoted,
              })}
            </span>
          </div>

          <button
            className="PredictionSummary-toggle"
            onclick={() => {
              this.showDetails = !this.showDetails;
              m.redraw();
            }}
          >
            {this.showDetails ? (
              <span><i className="fas fa-chevron-up" /> Hide details</span>
            ) : (
              <span><i className="fas fa-chevron-down" /> Show details</span>
            )}
          </button>

          {this.showDetails ? (
            <div className="PredictionSummary-details">
              {correctPredictions.length > 0 ? (
                <div className="PredictionSummary-section PredictionSummary-section--correct">
                  <span className="PredictionSummary-section-label">
                    <i className="fas fa-check" /> {app.translator.trans('huseyinfiliz-awards.forum.prediction.correct')}
                  </span>
                  <div className="PredictionSummary-categories">
                    {correctPredictions.map((r) => (
                      <span key={r.category.id()} className="PredictionSummary-category">
                        {r.category.name()}
                      </span>
                    ))}
                  </div>
                </div>
              ) : null}

              {wrongPredictions.length > 0 ? (
                <div className="PredictionSummary-section PredictionSummary-section--wrong">
                  <span className="PredictionSummary-section-label">
                    <i className="fas fa-times" /> {app.translator.trans('huseyinfiliz-awards.forum.prediction.wrong')}
                  </span>
                  <div className="PredictionSummary-categories">
                    {wrongPredictions.map((r) => (
                      <span key={r.category.id()} className="PredictionSummary-category">
                        {r.category.name()}
                      </span>
                    ))}
                  </div>
                </div>
              ) : null}
            </div>
          ) : null}
        </div>
      </div>
    );
  }
}
