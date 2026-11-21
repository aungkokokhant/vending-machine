<?php

namespace App\Http\Controllers;

use App\Contracts\ProductRepository;
use App\Contracts\Purchasing;
use App\Contracts\TransactionRepository;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\PurchaseRequest;
use App\Routing\PurchaseRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductsController extends Controller
{
    public function __construct(private ProductRepository $products, private Purchasing $purchasing, private TransactionRepository $transactions) {}

    public function index(Request $request): View
    {
        $products = $this->products->paginate($request->string('sort', 'name', strict: true)->toString(), $request->string('direction', 'asc', strict: true)->toString(), max(1, $request->integer('page', 1, strict: true)))->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.form', ['product' => null]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated());

        return to_route('admin.products.show', $product['id'])->with('success', 'Product created.');
    }

    public function show(int $id): View
    {
        $product = $this->products->find($id);
        abort_unless($product, 404);

        return view('products.show', compact('product'));
    }

    public function edit(int $id): View
    {
        $product = $this->products->find($id);
        abort_unless($product, 404);

        return view('products.form', compact('product'));
    }

    public function update(ProductRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->products->update($id, $request->validated()), 404);

        return to_route('admin.products.show', $id)->with('success', 'Product updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->products->delete($id), 404);

        return to_route('products.index')->with('success', 'Product deleted. Purchase history has been kept.');
    }

    public function confirmDelete(int $id): View
    {
        $product = $this->products->find($id);
        abort_unless($product, 404);

        return view('products.delete', compact('product'));
    }

    public function purchaseForm(string $slug): View
    {
        $product = $this->products->findBySlug($slug);
        abort_unless($product, 404);

        return view('products.purchase', compact('product'));
    }

    #[PurchaseRoute('/products/{slug}/purchase', 'products.purchase')]
    public function purchase(PurchaseRequest $request, string $slug): RedirectResponse
    {
        $transaction = $this->purchasing->purchase((int) $request->session()->get('user_id'), $slug, (int) $request->validated('quantity'));

        return to_route('transactions.index')->with('success', 'Purchase #'.$transaction['id'].' completed. Total: '.$transaction['total_price'].' USD.');
    }

    public function history(Request $request): View
    {
        $transactions = $this->transactions->forUser((int) $request->session()->get('user_id'), max(1, $request->integer('page', 1, strict: true)));

        return view('transactions.index', compact('transactions'));
    }
}
