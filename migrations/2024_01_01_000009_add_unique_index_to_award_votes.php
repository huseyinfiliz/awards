<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('award_votes', function (Blueprint $table) {
            $table->unique(['nominee_id', 'user_id']);
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('award_votes', function (Blueprint $table) {
            $table->dropUnique(['nominee_id', 'user_id']);
        });
    },
];
