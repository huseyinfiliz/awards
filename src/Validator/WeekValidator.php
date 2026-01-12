<?php

namespace HuseyinFiliz\Pickem\Validator;

use Flarum\Foundation\AbstractValidator;
use Illuminate\Validation\Rule;

class WeekValidator extends AbstractValidator
{
    protected function getRules()
    {
        $required = $this->model ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:100'],
            'seasonId' => [
                'nullable', // Sezonsuz haftalara izin ver
                'integer',
                Rule::exists('pickem_seasons', 'id') // Ama ID varsa, 'seasons' tablosunda var olmalı
            ],
            'weekNumber' => ['nullable', 'integer'],
        ];
    }
}