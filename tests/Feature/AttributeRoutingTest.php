<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductsController;
use App\Http\Middleware\RequireSessionUser;
use App\Routing\PurchaseRoute;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

class AttributeRoutingTest extends TestCase
{
    public function test_purchase_route_is_registered_from_its_php_attribute_and_protected(): void
    {
        $attribute = (new ReflectionMethod(ProductsController::class, 'purchase'))->getAttributes(PurchaseRoute::class)[0]->newInstance();
        $route = Route::getRoutes()->getByName($attribute->name);
        $this->assertSame(ltrim($attribute->uri, '/'), $route->uri());
        $this->assertSame(['POST'], $route->methods());
        $this->assertContains(RequireSessionUser::class, $route->gatherMiddleware());
        $this->assertSame(url('/products/coke/purchase'), route('products.purchase', 'coke'));
    }
}
