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
use HuseyinFiliz\Awards\Service\VoteLimitService;

class CreateOtherSuggestionController extends AbstractCreateController
{
    public $serializer = OtherSuggestionSerializer::class;

    protected $translator;
    protected $voteLimitService;

    public function __construct(TranslatorInterface $translator, VoteLimitService $voteLimitService)
    {
        $this->translator = $translator;
        $this->voteLimitService = $voteLimitService;
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

        // Check vote limit (unless unlimited)
        if (!$this->voteLimitService->isUnlimited()) {
            if (!$this->voteLimitService->canVote($categoryId, $actor->id)) {
                throw new ValidationException([
                    'message' => $this->translator->trans('huseyinfiliz-awards.forum.error.vote_quota_exhausted')
                ]);
            }
        }

        return OtherSuggestion::create([
            'category_id' => $categoryId,
            'user_id' => $actor->id,
            'name' => $name,
            'status' => 'pending',
        ]);
    }
}
