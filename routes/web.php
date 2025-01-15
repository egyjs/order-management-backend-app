<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $markdown = file_get_contents(base_path('README.md'));

    // markdown
    return (new League\CommonMark\CommonMarkConverter())->convert($markdown);
});
