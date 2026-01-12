<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('award_categories', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('award_id');
    $table->string('name', 255);
    $table->string('slug', 255);
    $table->text('description')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('allow_other')->default(false);
    $table->timestamps();

    $table->foreign('award_id')->references('id')->on('awards')->onDelete('cascade');
    $table->unique(['award_id', 'slug']);
});
