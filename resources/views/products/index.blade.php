@extends('layouts.app')
@section('title', 'Products')
@section('body-class', 'catalog-page')
@section('content')
<section class="catalog-hero">
    <div class="hero-copy"><span class="eyebrow"><span class="eyebrow-line"></span> THE EVERYDAY REFRESH</span><h1>Make room for<br>a <em>little refresh.</em></h1><p>Your favorites. Ready when you are.<br>Find your drink and make the moment yours.</p><a class="hero-link" href="#collection">Explore the collection <span aria-hidden="true">↗</span></a></div>
    <div class="hero-art" aria-hidden="true"><span class="hero-orbit"></span><span class="hero-spark">✳</span><span class="hero-word">REFRESH</span><div class="hero-drink hero-drink-left">@include('partials.drink', ['drinkName' => 'Pepsi'])</div><div class="hero-drink hero-drink-right">@include('partials.drink', ['drinkName' => 'Coke'])</div><span class="hero-stamp">GOOD DRINKS.<br>GREAT LITTLE BREAKS.</span></div>
</section>
<div class="collection-heading" id="collection"><div><span class="eyebrow">PICK YOUR MOMENT</span><h2>The refreshment collection<span class="brand-dot">.</span></h2></div>@if(session('role') === 'admin')<a class="button" href="{{ route('admin.products.create') }}"><span aria-hidden="true">+</span> Add product</a>@endif</div>
<div class="catalog-toolbar"><p><span class="collection-count">{{ $products->total() }}</span> {{ Str::plural('product', $products->total()) }} available</p><form class="sort-form" method="get" action="{{ route('products.index') }}"><label for="sort">Sort by</label><select id="sort" name="sort">@foreach(['name' => 'Name', 'price' => 'Price', 'quantity_available' => 'Stock'] as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'name') === $value)>{{ $label }}</option>@endforeach</select><select name="direction" aria-label="Sort direction"><option value="asc" @selected(request('direction', 'asc') === 'asc')>Ascending</option><option value="desc" @selected(request('direction') === 'desc')>Descending</option></select><button class="button button-small button-outline" type="submit">Apply</button></form></div>
<div class="product-grid">
    @forelse($products as $product)
        <article class="product-card">
            @php($style = str_contains(strtolower($product['name']), 'pepsi') ? 'blue' : (str_contains(strtolower($product['name']), 'water') ? 'aqua' : 'red'))
            <div class="product-art art-{{ $style }}"><span class="stock-badge {{ $product['quantity_available'] ? '' : 'sold-out' }}"><span class="status-dot" aria-hidden="true"></span>{{ $product['quantity_available'] ? 'In stock' : 'Sold out' }}</span><span class="product-number" aria-hidden="true">{{ str_pad((string) (($products->firstItem() ?? 1) + $loop->index), 2, '0', STR_PAD_LEFT) }}</span><span class="product-art-ring" aria-hidden="true"></span>@include('partials.drink', ['drinkName' => $product['name']])<span class="product-art-caption" aria-hidden="true">YOUR NEXT FAVORITE</span></div>
            <div class="product-info"><div class="product-title"><h3>{{ $product['name'] }}</h3><p class="price">{{ $product['price'] }} <small>USD</small></p></div><p class="stock-count"><span class="stock-line" aria-hidden="true"></span>{{ $product['quantity_available'] }} units available</p><a class="button {{ $product['quantity_available'] ? '' : 'button-outline' }} button-full" href="{{ route('products.purchase.form', $product['slug']) }}">{{ $product['quantity_available'] ? 'Buy now' : 'View product' }} <span class="button-arrow" aria-hidden="true">↗</span></a>@if(session('role') === 'admin')<div class="admin-links"><a href="{{ route('admin.products.show', $product['id']) }}">Details</a><a href="{{ route('admin.products.edit', $product['id']) }}">Edit</a><a class="danger-link" href="{{ route('admin.products.delete', $product['id']) }}">Delete</a></div>@endif</div>
        </article>
    @empty
        <div class="panel empty-state"><h2>No products yet</h2><p class="muted">New refreshments will appear here when they are added.</p></div>
    @endforelse
</div>
{{ $products->links() }}
@endsection
