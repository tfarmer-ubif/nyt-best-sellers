<?php

namespace App\Http\Controllers;

use App\Http\Requests\BestSellersRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function bestSellers(BestSellersRequest $request): JsonResponse {
        return response()->json();
    }
}
