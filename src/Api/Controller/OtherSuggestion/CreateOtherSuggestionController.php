<?php

namespace HuseyinFiliz\Awards\Api\Controller\OtherSuggestion;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\OtherSuggestionSerializer;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use HuseyinFiliz\Awards\Models\Category;
use HuseyinFiliz\Awards\Models\Vote;
use Flarum\Foundation\ValidationException;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateOtherSuggestionController extends AbstractCreateController
{
    public $serializer = OtherSuggestionSerializer::class;

    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.vote');

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $categoryId = Arr::get($data, 'categoryId');
        $name = trim(Arr::get($data, 'name'));

        // Empty check
        if (empty($name)) {
            throw new ValidationException([
                'name' => $this->translator->trans('validation.required', ['attribute' => 'name'])
            ]);
        }

        // Length check
        if (mb_strlen($name) > 255) {
            throw new ValidationException([
                'name' => $this->translator->trans('validation.max.string', ['attribute' => 'name', 'max' => 255])
            ]);
        }

        $category = Category::with('award')->findOrFail($categoryId);

        // Check if category allows other suggestions
        if (!$category->allow_other) {
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.other_not_allowed')
            ]);
        }

        // Check if voting is open
        if (!$category->award->isVotingOpen()) {
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.voting_closed')
            ]);
        }

        // Get votes per category limit from settings (default: 1, 0 = unlimited)
        $votesPerCategory = (int) resolve('flarum.settings')->get('huseyinfiliz-awards.votes_per_category', 1);

        // If unlimited votes, allow unlimited suggestions
        if ($votesPerCategory === 0) {
            return OtherSuggestion::create([
                'category_id' => $categoryId,
                'user_id' => $actor->id,
                'name' => $name,
                'status' => 'pending',
            ]);
        }

        // Count user's existing votes in this category
        $existingVotes = Vote::where('category_id', $categoryId)
            ->where('user_id', $actor->id)
            ->count();

        // Count user's pending suggestions in this category
        $pendingSuggestions = OtherSuggestion::where('category_id', $categoryId)
            ->where('user_id', $actor->id)
            ->where('status', 'pending')
            ->count();

        // Calculate available slots: votes_per_category - existing_votes - pending_suggestions
        $availableSlots = $votesPerCategory - $existingVotes - $pendingSuggestions;

        if ($availableSlots <= 0) {
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.vote_quota_exhausted')
            ]);
        }

        return OtherSuggestion::create([
            'category_id' => $categoryId,
            'user_id' => $actor->id,
            'name' => $name,
            'status' => 'pending',
        ]);
    }
}
