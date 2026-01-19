<?php

namespace HuseyinFiliz\Awards\Api\Controller\Category;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use HuseyinFiliz\Awards\Models\Category;

class AutocompleteCategoriesController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('awards.manage');

        $query = Arr::get($request->getQueryParams(), 'filter.q', '');

        $categories = Category::where('name', 'like', "%{$query}%")
            ->select('name', 'description')
            ->distinct('name')
            ->limit(10)
            ->get();

        return new JsonResponse([
            'data' => $categories->map(function ($category) {
                return [
                    'type' => 'award-category-suggestions',
                    'attributes' => [
                        'name' => $category->name,
                        'description' => $category->description,
                    ]
                ];
            })->toArray()
        ]);
    }
}
