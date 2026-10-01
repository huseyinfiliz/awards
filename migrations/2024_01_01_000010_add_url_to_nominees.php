<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('award_nominees', function (Blueprint $table) {
            $table->string('url', 1000)->nullable();
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('award_nominees', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    },
];
