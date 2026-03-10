<?php

namespace HuseyinFiliz\Awards\Forum\Controller;

use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class AwardsController
{
    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function __invoke(Document $document, ServerRequestInterface $request): Document
    {
        $actor = RequestUtil::getActor($request);
        $navTitle = $this->settings->get('huseyinfiliz-awards.nav_title', 'Awards');

        $document->title = $navTitle;

        return $document;
    }
}
