<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ProductRepository;
use App\Contracts\Purchasing;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductsController extends Controller
{
    public function __construct(private ProductRepository $products, private Purchasing $purchasing) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->products->paginate($request->string('sort', 'name', strict: true)->toString(), $request->string('direction', 'asc', strict: true)->toString(), max(1, $request->integer('page', 1, strict: true)))->withQueryString());
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->products->find($id);
        abort_unless($product, 404);

        return response()->json(['data' => $product]);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->products->create($request->validated())], 201);
    }

    public function update(ProductRequest $request, int $id): JsonResponse
    {
        $product = $this->products->update($id, $request->validated());
        abort_unless($product, 404);

        return response()->json(['data' => $product]);
    }

    public function destroy(int $id): Response
    {
        abort_unless($this->products->delete($id), 404);

        return response()->noContent();
    }

    public function purchase(PurchaseRequest $request, string $slug): JsonResponse
    {
        return response()->json(['data' => $this->purchasing->purchase($request->attributes->get('current_user')['id'], $slug, (int) $request->validated('quantity'))], 201);
    }
}
