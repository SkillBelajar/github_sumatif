<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['message' => 'Asesmen Sumatif API v0.11', 'status' => 'ok'];
});
