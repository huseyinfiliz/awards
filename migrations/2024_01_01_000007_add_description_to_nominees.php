<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::addColumns('award_nominees', [
    'description' => ['text', 'nullable' => true, 'after' => 'name'],
]);
