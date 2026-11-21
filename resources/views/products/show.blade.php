@extends('layouts.app')
@section('title', $product['name'])
@section('content')
<a class="back-link" href="{{ route('products.index') }}">← Back to products</a>
<div class="page-heading"><div><span class="eyebrow">PRODUCT DETAILS</span><h1>{{ $product['name'] }}</h1></div><a class="button" href="{{ route('admin.products.edit', $product['id']) }}">Edit product</a></div>
<section class="panel narrow-panel"><dl class="detail-list"><div><dt>Product ID</dt><dd>#{{ $product['id'] }}</dd></div><div><dt>Price</dt><dd>{{ $product['price'] }} USD</dd></div><div><dt>Available quantity</dt><dd>{{ $product['quantity_available'] }}</dd></div><div><dt>Purchase URL</dt><dd><a href="{{ route('products.purchase.form', $product['slug']) }}">{{ $product['slug'] }}</a></dd></div></dl><div class="form-actions"><a class="button button-outline" href="{{ route('products.purchase.form', $product['slug']) }}">Purchase</a><a class="danger-link" href="{{ route('admin.products.delete', $product['id']) }}">Delete product</a></div></section>
@endsection
