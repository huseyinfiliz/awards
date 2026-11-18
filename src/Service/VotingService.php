<?php

namespace HuseyinFiliz\Awards\Service;

use Flarum\Foundation\ValidationException;
use Flarum\User\User;
use HuseyinFiliz\Awards\Models\Category;
use HuseyinFiliz\Awards\Models\Nominee;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use HuseyinFiliz\Awards\Models\Vote;
use Illuminate\Support\Str;
use Symfony\Contracts\Translation\TranslatorInterface;

class VotingService
{
    public function __construct(
        protected VoteLimitService $limits,
        protected TranslatorInterface $translator,
    ) {
    }

    public function vote(Vote $vote): Vote
    {
        $categoryId = (int) Nominee::findOrFail($vote->nominee_id)->category_id;

        return $vote->getConnection()->transaction(function () use ($vote, $categoryId) {
            $category = $this->lockCategory($categoryId);
            $this->assertVotingOpen($category);
            $nominee = Nominee::where('category_id', $category->id)->findOrFail($vote->nominee_id);
            $vote->category_id = $category->id;

            if (Vote::where('nominee_id', $nominee->id)->where('user_id', $vote->user_id)->exists()) {
                $this->fail('already_voted');
            }

            $this->replaceVoteIfNecessary($category->id, $vote->user_id);
            $this->assertAvailableSlot($category->id, $vote->user_id);
            $vote->save();

            return $vote;
        });
    }

    public function suggest(OtherSuggestion $suggestion): OtherSuggestion
    {
        return $suggestion->getConnection()->transaction(function () use ($suggestion) {
            $category = $this->lockCategory($suggestion->category_id);
            $this->assertVotingOpen($category);
            if (!$category->allow_other) {
                $this->fail('other_not_allowed');
            }

            $this->assertAvailableSlot($category->id, $suggestion->user_id);
            $suggestion->status = 'pending';
            $suggestion->merged_to_nominee_id = null;
            $suggestion->save();

            return $suggestion;
        });
    }

    public function moderate(OtherSuggestion $suggestion, string $action, ?int $nomineeId): OtherSuggestion
    {
        return $suggestion->getConnection()->transaction(function () use ($suggestion, $action, $nomineeId) {
            $category = $this->lockCategory($suggestion->getRawOriginal('category_id'));
            $suggestion = OtherSuggestion::where('category_id', $category->id)->lockForUpdate()->findOrFail($suggestion->id);

            if ($suggestion->status !== 'pending') {
                throw new ValidationException(['message' => 'This suggestion has already been processed.']);
            }
            if (!in_array($action, ['approve', 'reject', 'merge'], true)) {
                throw new ValidationException(['action' => 'Select a valid moderation action.']);
            }

            if ($action === 'reject') {
                $suggestion->status = 'rejected';
                $suggestion->save();

                return $suggestion;
            }

            if ($action === 'merge') {
                $nominee = Nominee::where('category_id', $category->id)->findOrFail($nomineeId);
            } else {
                $baseSlug = Str::slug($suggestion->name) ?: 'nominee';
                $slug = $baseSlug;
                $counter = 2;
                while (Nominee::where('category_id', $category->id)->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter++;
                }
                $nominee = Nominee::create([
                    'category_id' => $category->id,
                    'name'        => $suggestion->name,
                    'slug'        => $slug,
                    'sort_order'  => 999,
                ]);
            }

            // Release the reserved slot before converting the suggestion into a vote.
            $suggestion->status = $action === 'approve' ? 'approved' : 'merged';
            $suggestion->merged_to_nominee_id = $nominee->id;
            $suggestion->save();

            if ($suggestion->user_id) {
                $existing = Vote::where('nominee_id', $nominee->id)->where('user_id', $suggestion->user_id)->exists();
                if (!$existing) {
                    $this->replaceVoteIfNecessary($category->id, $suggestion->user_id);
                    $this->assertAvailableSlot($category->id, $suggestion->user_id);
                    Vote::create([
                        'category_id' => $category->id,
                        'user_id'     => $suggestion->user_id,
                        'nominee_id'  => $nominee->id,
                    ]);
                }
            }

            return $suggestion;
        });
    }

    public function delete(Vote|OtherSuggestion $model, User $actor): void
    {
        $model->getConnection()->transaction(function () use ($model, $actor) {
            $this->lockCategory($model->category_id);
            // Moderation may have changed the suggestion while this request waited.
            $model->refresh();
            $actor->assertCan('delete', $model);
            $model->delete();
        });
    }

    protected function lockCategory(int $categoryId): Category
    {
        // A category row exists even when no votes exist. All quota writers lock it first.
        return Category::query()->lockForUpdate()->findOrFail($categoryId);
    }

    protected function assertVotingOpen(Category $category): void
    {
        if (!$category->award->isVotingOpen()) {
            $this->fail('voting_closed');
        }
    }

    protected function replaceVoteIfNecessary(int $categoryId, int $userId): void
    {
        if ($this->limits->isSingleVoteMode()) {
            Vote::where('category_id', $categoryId)->where('user_id', $userId)->delete();
        }
    }

    protected function assertAvailableSlot(int $categoryId, int $userId): void
    {
        if (!$this->limits->canVote($categoryId, $userId)) {
            $this->fail('vote_quota_exhausted');
        }
    }

    protected function fail(string $key): never
    {
        throw new ValidationException([
            'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.'.$key),
        ]);
    }
}
