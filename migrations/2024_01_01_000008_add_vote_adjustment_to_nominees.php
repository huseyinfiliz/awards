<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('award_nominees', function (Blueprint $table) {
            $table->integer('vote_adjustment')->default(0);
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('award_nominees', function (Blueprint $table) {
            $table->dropColumn('vote_adjustment');
        });
    },
];
