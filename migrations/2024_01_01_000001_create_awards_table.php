<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('awards', function (Blueprint $table) {
    $table->increments('id');
    $table->string('name', 255);
    $table->string('slug', 255)->unique();
    $table->integer('year')->nullable();
    $table->text('description')->nullable();
    $table->dateTime('starts_at')->nullable();
    $table->dateTime('ends_at')->nullable();
    $table->enum('status', ['draft', 'active', 'ended', 'published'])->default('draft');
    $table->boolean('show_live_votes')->default(false);
    $table->string('image_url', 500)->nullable();
    $table->timestamps();
});
