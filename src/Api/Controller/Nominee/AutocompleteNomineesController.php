<?php

namespace HuseyinFiliz\Awards\Api\Controller\Nominee;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use HuseyinFiliz\Awards\Models\Nominee;

class AutocompleteNomineesController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $query = Arr::get($request->getQueryParams(), 'filter.q', '');

        $nominees = Nominee::where('name', 'like', "%{$query}%")
            ->select('name', 'description', 'image_url')
            ->distinct('name')
            ->limit(10)
            ->get();

        return new JsonResponse([
            'data' => $nominees->map(function ($nominee) {
                return [
                    'type' => 'award-nominee-suggestions',
                    'attributes' => [
                        'name' => $nominee->name,
                        'description' => $nominee->description,
                        'imageUrl' => $nominee->image_url,
                    ]
                ];
            })->toArray()
        ]);
    }
}
