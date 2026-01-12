<?php

namespace HuseyinFiliz\Pickem\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use HuseyinFiliz\Pickem\Api\Serializer\PickSerializer;
use HuseyinFiliz\Pickem\Event;
use HuseyinFiliz\Pickem\Pick;
use HuseyinFiliz\Pickem\Job\RecalculateUserScoreJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreatePickController extends AbstractCreateController
{
    public $serializer = PickSerializer::class;
    public $include = ['event', 'event.homeTeam', 'event.awayTeam', 'user'];

    protected $bus;
    protected $translator;

    public function __construct(Dispatcher $bus, Translator $translator)
    {
        $this->bus = $bus;
        $this->translator = $translator;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('pickem.makePicks');

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);
        
        $eventId = Arr::get($data, 'eventId');
        $selectedOutcome = Arr::get($data, 'selectedOutcome');

        // Eksik veri kontrolü
        if (!$eventId || !$selectedOutcome) {
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-pickem.lib.messages.invalid_outcome')
            ]);
        }

        $event = Event::with(['homeTeam', 'awayTeam', 'week'])->findOrFail($eventId);

        // Süre kontrolü (Cutoff)
        if (!$event->canPick()) {
            // GÜNCELLENDİ: Özel 'cutoff_passed' yerine genel 'invalid_outcome' kullanıyoruz.
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-pickem.lib.messages.invalid_outcome')
            ]);
        }

        // Beraberlik kontrolü
        if ($selectedOutcome === Event::RESULT_DRAW && !$event->allow_draw) {
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-pickem.lib.messages.invalid_outcome')
            ]);
        }

        // Geçerli sonuç tipi kontrolü (home, away, draw)
        $validOutcomes = [Event::RESULT_HOME, Event::RESULT_AWAY, Event::RESULT_DRAW];
        if (!in_array($selectedOutcome, $validOutcomes)) {
            throw new ValidationException([
                'message' => $this->translator->trans('huseyinfiliz-pickem.lib.messages.invalid_outcome')
            ]);
        }

        $pick = Pick::firstOrNew([
            'user_id' => $actor->id,
            'event_id' => $eventId,
        ]);

        $isUpdate = $pick->exists;
        $recalculateScore = false; 

        $pick->selected_outcome = $selectedOutcome;

        if ($isUpdate && $pick->isDirty('selected_outcome')) {
            $pick->is_correct = null;
            
            if ($event->isFinished() || $event->isClosed()) {
                 $recalculateScore = true;
            }
        }

        $pick->save();

        if ($recalculateScore) {
            $this->bus->dispatch(new RecalculateUserScoreJob($actor->id));
        }

        $pick->load(['event.homeTeam', 'event.awayTeam', 'event.week', 'user']);

        return $pick;
    }
}