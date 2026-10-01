<?php

namespace HuseyinFiliz\Awards\Api\Controller\OtherSuggestion;

use Flarum\Http\RequestUtil;
use HuseyinFiliz\Awards\Models\OtherSuggestion;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListUserSuggestionsController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $categoryId = Arr::get($request->getQueryParams(), 'filter.category');

        $query = OtherSuggestion::with(['category'])
            ->where('user_id', $actor->id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $suggestions = $query->orderBy('created_at', 'desc')->get();

        $data = [];
        foreach ($suggestions as $suggestion) {
            /** @var OtherSuggestion $suggestion */
            $item = [
                'type'       => 'award-other-suggestions',
                'id'         => (string) $suggestion->id,
                'attributes' => [
                    'name'      => $suggestion->name,
                    'status'    => $suggestion->status,
                    'createdAt' => $suggestion->created_at?->toIso8601String(),
                ],
                'relationships' => [],
            ];

            if ($suggestion->relationLoaded('category') && $suggestion->category) {
                $item['relationships']['category'] = [
                    'data' => [
                        'type' => 'award-categories',
                        'id'   => (string) $suggestion->category->id,
                    ],
                ];
            }

            $data[] = $item;
        }

        return new JsonResponse([
            'data' => $data,
        ]);
    }
}
