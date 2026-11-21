<?php

namespace App\Http\Controllers\Api;

use App\Contracts\TransactionRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    public function __construct(private TransactionRepository $transactions) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->transactions->forUser($request->attributes->get('current_user')['id'], max(1, $request->integer('page', 1, strict: true)))->withQueryString());
    }
}
