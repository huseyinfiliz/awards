<?php

namespace HuseyinFiliz\Awards\Api\Controller\Admin;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use HuseyinFiliz\Awards\Models\Nominee;

class UpdateNomineeVotesController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $nominee = Nominee::findOrFail($id);

        if (Arr::has($data, 'voteAdjustment')) {
            $nominee->vote_adjustment = (int) Arr::get($data, 'voteAdjustment');
            $nominee->save();
        }

        $nominee = $nominee->fresh();

        return new JsonResponse([
            'data' => [
                'type' => 'award-nominees',
                'id' => (string) $nominee->id,
                'attributes' => [
                    'voteAdjustment' => $nominee->vote_adjustment ?? 0,
                    'realVoteCount' => $nominee->real_vote_count,
                    'voteCount' => $nominee->vote_count,
                ],
            ],
        ]);
    }
}
