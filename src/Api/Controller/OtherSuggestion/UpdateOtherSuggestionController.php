<?php

namespace HuseyinFiliz\Awards\Api\Controller\OtherSuggestion;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use HuseyinFiliz\Awards\Api\Serializer\OtherSuggestionSerializer;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use HuseyinFiliz\Awards\Models\Nominee;
use HuseyinFiliz\Awards\Models\Vote;

class UpdateOtherSuggestionController extends AbstractShowController
{
    public $serializer = OtherSuggestionSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $suggestion = OtherSuggestion::findOrFail($id);
        $action = Arr::get($data, 'action'); // 'approve', 'reject', 'merge'
        $mergeToNomineeId = Arr::get($data, 'mergeToNomineeId');

        switch ($action) {
            case 'approve':
                // Create new nominee from suggestion
                $nominee = Nominee::create([
                    'category_id' => $suggestion->category_id,
                    'name' => $suggestion->name,
                    'slug' => Str::slug($suggestion->name),
                    'sort_order' => 999,
                ]);

                // Create vote for the user who suggested
                Vote::updateOrCreate(
                    ['category_id' => $suggestion->category_id, 'user_id' => $suggestion->user_id],
                    ['nominee_id' => $nominee->id]
                );

                $suggestion->status = 'approved';
                $suggestion->merged_to_nominee_id = $nominee->id;
                break;

            case 'reject':
                $suggestion->status = 'rejected';
                break;

            case 'merge':
                if (!$mergeToNomineeId) {
                    throw new \InvalidArgumentException('mergeToNomineeId is required for merge action');
                }

                // Create vote for the user who suggested, pointing to merged nominee
                Vote::updateOrCreate(
                    ['category_id' => $suggestion->category_id, 'user_id' => $suggestion->user_id],
                    ['nominee_id' => $mergeToNomineeId]
                );

                $suggestion->status = 'merged';
                $suggestion->merged_to_nominee_id = $mergeToNomineeId;
                break;
        }

        $suggestion->save();
        return $suggestion;
    }
}
