<?php

use Flarum\Settings\DatabaseSettingsRepository;
use Illuminate\Database\ConnectionInterface;

return [
    'up' => function (ConnectionInterface $db) {
        $settings = new DatabaseSettingsRepository($db);

        $settings->set('huseyinfiliz-awards.nav_title', 'Awards');
        $settings->set('huseyinfiliz-awards.votes_per_category', '1');
    },
    'down' => function (ConnectionInterface $db) {
        $settings = new DatabaseSettingsRepository($db);

        $settings->delete('huseyinfiliz-awards.nav_title');
        $settings->delete('huseyinfiliz-awards.votes_per_category');
    },
];
