<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $data = app(\App\Repositories\PostRepository::class)
        ->search('vero')
        ->get();
    dd($data->toArray());
});
