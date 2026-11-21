<?php

namespace App\Routing;

use Illuminate\Support\Facades\Route;
use ReflectionClass;

class AttributeRouteRegistrar
{
    public static function register(string $controller): void
    {
        foreach ((new ReflectionClass($controller))->getMethods() as $method) {
            foreach ($method->getAttributes(PurchaseRoute::class) as $attribute) {
                $route = $attribute->newInstance();
                Route::match([$route->method], $route->uri, [$controller, $method->getName()])->name($route->name);
            }
        }
    }
}
