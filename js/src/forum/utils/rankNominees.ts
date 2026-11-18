import type Nominee from '../../common/models/Nominee';

export interface RankedNominee {
  nominee: Nominee;
  votes: number;
  rank: number | null;
}

export default function rankNominees(nominees: readonly (Nominee | undefined)[]): RankedNominee[] {
  const sorted = nominees
    .filter((nominee): nominee is Nominee => !!nominee)
    .map((nominee) => ({ nominee, votes: Math.max(0, nominee.voteCount() || 0) }))
    .sort((a, b) => b.votes - a.votes);

  let rank: number | null = null;

  return sorted.map((entry, index) => {
    if (entry.votes === 0) {
      rank = null;
    } else if (index === 0 || entry.votes !== sorted[index - 1].votes) {
      rank = index + 1;
    }

    return { ...entry, rank };
  });
}

export function previewNominees(ranked: readonly RankedNominee[]): RankedNominee[] {
  // Keep every tie within the first three places, rather than truncating a shared place.
  return ranked[0]?.rank ? ranked.filter((entry) => entry.rank !== null && entry.rank <= 3) : ranked.slice(0, 3);
}
