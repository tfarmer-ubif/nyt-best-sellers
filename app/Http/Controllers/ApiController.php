<?php

namespace App\Http\Controllers;

use App\Http\Requests\BestSellersRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    /**
     * @throws ConnectionException
     */
    public function bestSellers(BestSellersRequest $request): JsonResponse {
        $filters = $request->validated();

        //format the ISBNs as a semicolon separated string
        if (isset($filters['isbn'])) {
            $filters['isbn'] = implode(';', $filters['isbn']);
        }

        $response = Http::acceptJson()
            ->get(config('env.bestSellers.apiUrl'), [
                'api-key' => config('env.bestSellers.apiKey'),
                ...$filters
            ]);

        return response()->json($response->json());
    }
}
