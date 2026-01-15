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

        // Get suggestion limit from settings (default: 1, 0 = unlimited)
        $suggestionLimit = (int) resolve('flarum.settings')->get('huseyinfiliz-awards.suggestions_per_category', 1);

        // Count existing suggestions by this user for this category
        $existingCount = OtherSuggestion::where('category_id', $categoryId)
            ->where('user_id', $actor->id)
            ->count();

        // Check if user has reached the suggestion limit (0 = unlimited)
        if ($suggestionLimit > 0 && $existingCount >= $suggestionLimit) {
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.suggestion_limit_reached', [
                    '{limit}' => $suggestionLimit
                ])
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
