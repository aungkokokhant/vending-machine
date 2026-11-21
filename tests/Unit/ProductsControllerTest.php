<?php

namespace Tests\Unit;

use App\Contracts\ProductRepository;
use App\Contracts\Purchasing;
use App\Contracts\TransactionRepository;
use App\Http\Controllers\ProductsController;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProductsControllerTest extends TestCase
{
    private ProductRepository $products;

    private Purchasing $purchasing;

    private TransactionRepository $transactions;

    private ProductsController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = Mockery::mock(ProductRepository::class);
        $this->purchasing = Mockery::mock(Purchasing::class);
        $this->transactions = Mockery::mock(TransactionRepository::class);
        $this->controller = new ProductsController($this->products, $this->purchasing, $this->transactions);
    }

    public function test_index_uses_repository_pagination_without_a_database(): void
    {
        $page = new LengthAwarePaginator([['id' => 1, 'name' => 'Pepsi']], 1, 9);
        $this->products->shouldReceive('paginate')->once()->with('price', 'desc', 2)->andReturn($page);
        $view = $this->controller->index(Request::create('/products?sort=price&direction=desc&page=2'));
        $this->assertSame('products.index', $view->name());
        $this->assertSame($page, $view->getData()['products']);
    }

    public function test_store_passes_only_validated_data_to_repository(): void
    {
        $data = ['name' => 'Tea', 'price' => '1.125', 'quantity_available' => 2];
        $request = Mockery::mock(ProductRequest::class);
        $request->shouldReceive('validated')->once()->withNoArgs()->andReturn($data);
        $this->products->shouldReceive('create')->once()->with($data)->andReturn(['id' => 7]);
        $response = $this->controller->store($request);
        $this->assertSame(route('admin.products.show', 7), $response->getTargetUrl());
    }

    public function test_update_and_delete_delegate_to_mocked_repository(): void
    {
        $request = Mockery::mock(ProductRequest::class);
        $data = ['name' => 'Tea', 'price' => '2.125', 'quantity_available' => 0];
        $request->shouldReceive('validated')->andReturn($data);
        $this->products->shouldReceive('update')->once()->with(7, $data)->andReturn(['id' => 7]);
        $this->assertSame(route('admin.products.show', 7), $this->controller->update($request, 7)->getTargetUrl());
        $this->products->shouldReceive('delete')->once()->with(7)->andReturnTrue();
        $this->assertSame(route('products.index'), $this->controller->destroy(7)->getTargetUrl());
    }

    public function test_show_handles_a_missing_product_without_a_database(): void
    {
        $this->products->shouldReceive('find')->once()->with(99)->andReturnNull();
        $this->expectException(HttpException::class);
        $this->controller->show(99);
    }

    public function test_purchase_delegates_to_injected_service_with_session_identity(): void
    {
        $request = Mockery::mock(PurchaseRequest::class);
        $request->shouldReceive('session')->andReturn(session());
        session()->put('user_id', 4);
        $request->shouldReceive('validated')->once()->with('quantity')->andReturn('3');
        $this->purchasing->shouldReceive('purchase')->once()->with(4, 'pepsi', 3)->andReturn(['id' => 5, 'total_price' => '20.655']);
        $this->assertSame(route('transactions.index'), $this->controller->purchase($request, 'pepsi')->getTargetUrl());
    }

    public function test_purchase_propagates_stock_validation_from_the_mocked_service(): void
    {
        $request = Mockery::mock(PurchaseRequest::class);
        $request->shouldReceive('session')->andReturn(session());
        session()->put('user_id', 4);
        $request->shouldReceive('validated')->with('quantity')->andReturn(99);
        $this->purchasing->shouldReceive('purchase')->with(4, 'pepsi', 99)->andThrow(ValidationException::withMessages(['quantity' => 'Not enough stock.']));
        $this->expectException(ValidationException::class);
        $this->controller->purchase($request, 'pepsi');
    }
}
