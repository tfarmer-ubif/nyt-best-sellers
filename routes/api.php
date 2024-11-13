<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/1/nyt/best-sellers', [ApiController::class, 'bestSellers']);
