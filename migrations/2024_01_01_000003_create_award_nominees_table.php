<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('award_nominees', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('category_id');
    $table->string('name', 255);
    $table->string('slug', 255);
    $table->string('image_url', 500)->nullable();
    $table->json('metadata')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    $table->foreign('category_id')->references('id')->on('award_categories')->onDelete('cascade');
    $table->unique(['category_id', 'slug']);
});
