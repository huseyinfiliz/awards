<?php

namespace HuseyinFiliz\Pickem\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use HuseyinFiliz\Pickem\Api\Serializer\WeekSerializer;
use HuseyinFiliz\Pickem\Week;
use HuseyinFiliz\Pickem\Validator\WeekValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str; // EKLENDİ
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateWeekController extends AbstractShowController
{
    public $serializer = WeekSerializer::class;

    protected $validator;

    public function __construct(WeekValidator $validator)
    {
        $this->validator = $validator;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('pickem.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $week = Week::findOrFail($id);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        // --- YENİ MANTIK ---
        // 1. Gelen camelCase (weekNumber) veriyi snake_case (week_number) veriye dönüştür
        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[Str::snake($key)] = $value;
        }

        // 2. Modeli ve veriyi doğrula
        $this->validator->model = $week;
        $this->validator->assertValid($attributes);

        // 3. Modeli $fillable kullanarak doldur ve kaydet
        $week->fill($attributes);
        $week->save();
        // --- YENİ MANTIK SONU ---

        return $week;
    }
}