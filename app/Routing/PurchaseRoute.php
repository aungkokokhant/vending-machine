<?php

namespace App\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PurchaseRoute
{
    public function __construct(public string $uri, public string $name, public string $method = 'POST') {}
}
