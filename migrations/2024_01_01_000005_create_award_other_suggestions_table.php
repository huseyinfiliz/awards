<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('award_other_suggestions', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('category_id');
    $table->unsignedInteger('user_id');
    $table->string('name', 255);
    $table->enum('status', ['pending', 'approved', 'rejected', 'merged'])->default('pending');
    $table->unsignedInteger('merged_to_nominee_id')->nullable();
    $table->timestamps();

    $table->foreign('category_id')->references('id')->on('award_categories')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('merged_to_nominee_id')->references('id')->on('award_nominees')->onDelete('set null');

    $table->index(['category_id', 'status']);
});
