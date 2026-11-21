@extends('layouts.app')
@section('title', 'Products')
@section('content')
<div class="page-heading"><div><span class="eyebrow">FRESH PICKS, READY TO GO</span><h1>Choose your refreshment<span class="brand-dot">.</span></h1><p class="muted">Your favorites, stocked and ready whenever you are.</p></div>@if(session('role') === 'admin')<a class="button" href="{{ route('admin.products.create') }}">+ Add product</a>@endif</div>
<div class="catalog-toolbar"><p><strong>{{ $products->total() }}</strong> {{ Str::plural('product', $products->total()) }} available</p><form class="sort-form" method="get" action="{{ route('products.index') }}"><label for="sort">Sort by</label><select id="sort" name="sort">@foreach(['name' => 'Name', 'price' => 'Price', 'quantity_available' => 'Stock'] as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'name') === $value)>{{ $label }}</option>@endforeach</select><select name="direction" aria-label="Sort direction"><option value="asc" @selected(request('direction', 'asc') === 'asc')>Ascending</option><option value="desc" @selected(request('direction') === 'desc')>Descending</option></select><button class="button button-small button-outline" type="submit">Apply</button></form></div>
<div class="product-grid">
    @forelse($products as $product)
        <article class="product-card">
            @php($style = str_contains(strtolower($product['name']), 'pepsi') ? 'blue' : (str_contains(strtolower($product['name']), 'water') ? 'aqua' : 'red'))
            <div class="product-art art-{{ $style }}"><span class="stock-badge {{ $product['quantity_available'] ? '' : 'sold-out' }}">{{ $product['quantity_available'] ? 'In stock' : 'Sold out' }}</span><div class="product-vessel vessel-{{ $style }}" aria-hidden="true"><span>{{ Str::upper(Str::limit($product['name'], 14)) }}</span><small>REFRESH YOUR DAY</small></div></div>
            <div class="product-info"><div class="product-title"><h2>{{ $product['name'] }}</h2><p class="price">{{ $product['price'] }} <small>USD</small></p></div><p class="stock-count">{{ $product['quantity_available'] }} units available</p><a class="button {{ $product['quantity_available'] ? '' : 'button-outline' }} button-full" href="{{ route('products.purchase.form', $product['slug']) }}">{{ $product['quantity_available'] ? 'Buy now' : 'View product' }} <span aria-hidden="true">→</span></a>@if(session('role') === 'admin')<div class="admin-links"><a href="{{ route('admin.products.show', $product['id']) }}">Details</a><a href="{{ route('admin.products.edit', $product['id']) }}">Edit</a><a class="danger-link" href="{{ route('admin.products.delete', $product['id']) }}">Delete</a></div>@endif</div>
        </article>
    @empty
        <div class="panel empty-state"><h2>No products yet</h2><p class="muted">New refreshments will appear here when they are added.</p></div>
    @endforelse
</div>
{{ $products->links() }}
@endsection
