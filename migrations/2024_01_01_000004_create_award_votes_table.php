<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('award_votes', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('nominee_id');
    $table->unsignedInteger('user_id');
    $table->unsignedInteger('category_id');
    $table->timestamps();

    $table->foreign('nominee_id')->references('id')->on('award_nominees')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('category_id')->references('id')->on('award_categories')->onDelete('cascade');

    // Index for efficient lookups
    $table->index(['category_id', 'user_id']);
    $table->index(['nominee_id']);
});
