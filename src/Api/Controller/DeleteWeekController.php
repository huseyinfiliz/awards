<?php

namespace HuseyinFiliz\Pickem\Api\Controller;

use HuseyinFiliz\Pickem\Week;

class DeleteWeekController extends AbstractDeleteControllerWithRelationCheck
{
    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return Week::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationName(): string
    {
        return 'events';
    }

    /**
     * {@inheritdoc}
     */
    protected function getErrorMessageKey(): string
    {
        // GÜNCELLENDİ: lib.validation.errors.week_in_use -> lib.messages.in_use
        return 'huseyinfiliz-pickem.lib.messages.in_use';
    }
}